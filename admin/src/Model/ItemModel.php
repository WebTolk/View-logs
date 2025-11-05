<?php
/**
 * @package       View logs
 * @version       2.2.0.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2025 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\Model;

use DateInvalidTimeZoneException;
use DateTime;
use DateTimeZone;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\File;
use RuntimeException;
use ZipArchive;

use function defined;

defined('_JEXEC') or die;

class ItemModel extends BaseModel
{
    /**
     * Unique end of line standardisation
     *
     * @var string
     * @since 2.1.0
     */
    private static string $EOLPlaceholder = '<<<ViewLogsEOL>>>';

    /**
     * Get the log file data
     *
     * @param   string  $filename
     *
     * @return array
     *
     * @since 2.1.0
     */
    public function getItem(string $filename = ''): array
    {
        $filename = (!empty($filename)) ? $filename : (string)$this->getState('log.filename');
        if (empty($filename)) {
            return [];
        }

        $log_path = $this->getState('log.path');

        if (!file_exists($log_path . DIRECTORY_SEPARATOR . $filename)) {
            throw new RuntimeException("File '$filename' does not exist");
        }

        $item = $this->parseLogFile($filename);
        foreach ($item as &$row) {
            if (array_key_exists('message', $row) && str_contains($row['message'], self::$EOLPlaceholder)) {
                $row['message'] = str_replace(self::$EOLPlaceholder, PHP_EOL, $row['message']);
            }
        }

        return $item;
    }

    /**
     * Parse Joomla log file
     *
     * @param   string  $filename
     *
     * @return array
     *
     * @since 2.0.0
     */
    private function parseLogFile(string $filename): array
    {
        $log_path = $this->getState('log.path');

        $file       = file_get_contents($log_path . DIRECTORY_SEPARATOR . $filename);
        $isDownload = ($this->getState('task') == 'download');

        $lines  = explode("\n", $file);
        $result = [];

        // Line with table headers contains `#Fields`
        [$headerLineIndex, $headers] = $this->getLogHeaders($filename);

        $headersCount = count($headers);

        if ($headerLineIndex === null) {
            return $result;
        }
        $currentEntry      = null;
        $log_string_length = $this->getState('log.string.length');
        for ($i = $headerLineIndex + 1; $i < count($lines); $i++) {
            if ($log_string_length == $i && !$isDownload) {
                break;
            }

            $line = trim($lines[$i]);
            if (empty($line)) {
                continue;
            }


            // Check if line starts from date string (format: 2025-09-22T17:32:43+00:00)
            if (preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2})?/', $line)) {
                if ($currentEntry !== null) {
                    $result[] = $currentEntry;
                }

                $columns = explode("\t", $line, $headersCount);
                if (count($columns) === $headersCount) {
                    $currentEntry = array_combine($headers, $columns);

                    if (isset($currentEntry['message'])) {
                        $currentEntry['message'] = str_replace(
                            ["\r\n", "\r", "\n", PHP_EOL],
                            self::$EOLPlaceholder,
                            $currentEntry['message']
                        );
                    }
                } else {
                    $currentEntry = null;
                }
            } elseif ($currentEntry !== null && isset($currentEntry['message'])) {
                // If the row does not start with a date, this is the data for the message column from the previous record.
                $currentEntry['message'] .= self::$EOLPlaceholder . str_replace(
                        ["\r\n", "\r", "\n", PHP_EOL],
                        self::$EOLPlaceholder,
                        $line
                    );
            }
        }

        if ($currentEntry !== null) {
            $result[] = $currentEntry;
        }

        return array_reverse($result);
    }

    /**
     * Get the log table headers and header line index
     *
     * @param   string  $filename
     *
     * @return array
     *
     * @since 2.0.0
     */
    public function getLogHeaders(string $filename = ''): array
    {
        $filename = (!empty($filename)) ? $filename : (string)$this->getState('log.filename');
        if (empty($filename)) {
            return [];
        }

        $log_path = $this->getState('log.path');

        if (!file_exists($filename = $log_path . DIRECTORY_SEPARATOR . $filename)) {
            throw new RuntimeException("File '$filename' does not exist");
        }

        $headers         = [];
        $headerLineIndex = null;

        $handle = fopen($filename, 'r');

        if ($handle === false) {
            throw new RuntimeException("Cannot open file: " . $filename);
        }

        $index = 0;
        while (($line = fgets($handle)) !== false) {
            if (strpos($line, '#Fields:') === 0) {
                $headerLineIndex = $index;

                $headerLine = str_replace('#Fields:', '', $line);
                $headers    = explode("\t", trim($headerLine));

                break;
            }
            $index++;
        }

        fclose($handle);


        return [
            $headerLineIndex,
            $headers
        ];
    }

    /**
     * Prepare CVS with {saveCVS()} and download it
     *
     * @param   string  $filename
     *
     *
     * @return string|void Download link if ajax or force download if not
     *
     * @throws DateInvalidTimeZoneException
     * @since 1.0.0
     */
    public function downloadFile(string $filename): string
    {
        $download_type = $this->getState('download_type', 'csv');
        $tmp_path      = $this->getState('tmp.path');

        if ($filename === 'PHP error log') {
            $data = [];
            $log  = $this->getPhpLog();
            foreach ($log as $item) {
                if (empty($item)) {
                    continue;
                }
                $tmp  = explode('] ', $item);
                $date = substr($tmp[0], 1, strlen($tmp[0]) - 1);
                $date = explode(' ', $date);
                $date = new DateTime($date[0] . 'T' . $date[1], new DateTimeZone($date[2]));
                $date = date_format($date, 'Y-m-d H:i:s');
                [$type, $msg] = explode(':  ', $tmp[1]);
                $data[] = [$date, $type, trim($msg)];
            }

            $csv_file_name = pathinfo(ini_get('error_log'))['filename'];
        } else {
            $data          = $this->parseLogFile($filename);
            $csv_file_name = pathinfo($tmp_path . '/' . $filename)['filename'];
        }

        $data = array_reverse($data);

        $file = $tmp_path . DIRECTORY_SEPARATOR . $csv_file_name . '_' . HTMLHelper::_(
                'date',
                time(),
                'Y-m-d-H-i-s'
            ) . '.csv';
        $bom  = ($download_type == 'csvbom');

        $this->saveCSV($file, $data, $bom ? ';' : ',', $bom);

        $download_url = new Uri(Uri::root());
        $download_url->setPath('/' . str_replace(JPATH_SITE, '', $file));

        if (!$this->getState('is.ajax')) {
            $this->file_force_download($file);
            unlink($file);
        }

        return $download_url->toString();
    }

    private function getPhpLog()
    {
        $a = [];

        if (($handle = fopen(ini_get('error_log'), 'r')) !== false) {
            while (!feof($handle)) {
                $data = fgets($handle);
                if ($data !== false) {
                    $a[] = $data;
                }
            }
            fclose($handle);
        }

        $a = array_reverse($a);

        return $a;
    }

    /**
     * Save log data to CSV file
     *
     * @param   string  $file       filename
     * @param   array   $data       log data
     * @param   string  $delimiter  CSV delimiter
     * @param   bool    $bom        BOM (for MS Exel)
     *
     *
     * @since 1.0.0
     */
    private function saveCSV(string $file, array $data, string $delimiter = ';', bool $bom = false): void
    {
        if (($handle = fopen($file, 'w')) !== false) {
            if ($bom) {
                fwrite($handle, "\xEF\xBB\xBF");
            }
            foreach ($data as $item) {
                fputcsv($handle, $item, $delimiter, '"', '\\', PHP_EOL);
            }
            fclose($handle);
        }
    }

    private function file_force_download($file)
    {
        set_time_limit(0);
        if (file_exists($file)) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Description: File Transfer');
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));

            return (bool)readfile($file);
        } else {
            return false;
        }
    }

    /**
     * Delete the specified log file
     *
     * @param   string  $filename
     *
     * @return bool
     *
     * @since 2.1.0
     */
    public function deleteFile(string $filename): bool
    {
        $log_path = $this->getState('log.path');
        $result   = File::delete($log_path . DIRECTORY_SEPARATOR . $filename);

        return $result;
    }

    /**
     * ZIP the log file. Return an array with `message` string and `result` bool.
     *
     * @param   string  $filename
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function archiveFile(string $filename): array
    {
        $archive_path = ComponentHelper::getParams('com_vlogs')->get('apath', 'tmp');
        $delAfterArch = (int)ComponentHelper::getParams('com_vlogs')->get('delafterarch', 0);

        $result = [
            'message' => '',
            'result'  => false
        ];

        if (!$archive_path) {
            $result['message'] = Text::_('COM_VLOGS_ARCHIVEFILE_NO_FOLDER');
            $result['result']  = false;

            return $result;
        }

        $archive_path = str_replace('\\', '/', JPATH_ROOT . '/' . $archive_path);

        if (!is_dir($archive_path)) {
            $result['message'] = Text::_('COM_VLOGS_ARCHIVEFILE_NO_EXISTS_FOLDER');
            $result['result']  = false;

            return $result;
        }

        $log_path = $this->getState('log.path');

        if ($filename !== 'PHP error log') {
            if (!extension_loaded('zip')) {
                $result['message'] = Text::_('COM_VLOGS_NO_PHPZIP');
                $result['result']  = false;

                return $result;
            }

            $zip = new ZipArchive();

            $archFile = pathinfo($log_path . DIRECTORY_SEPARATOR . $filename, PATHINFO_FILENAME) . '__' . date(
                    'Y-m-d_h-i-s'
                ) . '.zip';
            $archPath = $archive_path . '/' . $archFile;

            if ($zip->open($archPath, ZipArchive::CREATE) !== true) {
                $result['message'] = Text::_('COM_VLOGS_ARCHIVEFILE_ERROR_CREATE');
                $result['result']  = false;

                return $result;
            } else {
                $zip->addFile($log_path . '/' . $filename, $filename);
            }

            if ($delAfterArch) {
                $resultDel = unlink($log_path . '/' . $filename);
            }
            $result['message'] = Text::sprintf(
                'COM_VLOGS_ARCHIVEFILE_ALERT_' . (int)($delAfterArch && $resultDel),
                $filename,
                str_replace(str_replace('\\', '/', JPATH_ROOT), '', $archPath)
            );
            $result['result']  = true;

            return $result;
        } else {
            $result['message'] = Text::_('COM_VLOGS_NO_ARCHIVE_PHP_LOG') . '   ' . $filename;
            $result['result']  = false;

            return $result;
        }
    }

    protected function populateState()
    {
        $app      = Factory::getApplication();
        $config   = Factory::getContainer()->get('config');
        $log_path = str_replace('\\', '/', $config->get('log_path'));
        $this->setState('log.path', $log_path);

        $tmp_path = str_replace('\\', '/', $config->get('tmp_path', JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp'));
        $this->setState('tmp.path', $tmp_path);

        $filename = $app->getInput()->get('filename', '');
        $this->setState('log.filename', $filename);

        $slen = ComponentHelper::getParams('com_vlogs')->get('slen', 32768);
        $this->setState('log.string.length', $slen);

        $download_type = $app->getInput()->getString('download_type', 'csv');
        $this->setState('download_type', $download_type);

        $ajax = $app->getInput()->getBool('ajax', false);
        $this->setState('is.ajax', $ajax);
    }

    /**
     * For CSV logs
     *
     * @param   string  $file
     * @param   string  $delimiter
     *
     * @return array
     *
     * @since 1.0.0
     */
    private function getCSV(string $file, string $delimiter = ';'): array
    {
        $a    = [];
        $slen = ComponentHelper::getParams('com_vlogs')->get('slen', 32768);

        if (($handle = fopen($file, 'r')) !== false) {
            while (!feof($handle)) {
                $data = fgetcsv($handle, $slen, $delimiter);
                if ($data !== false) {
                    $a[] = $data;
                }
            }
            fclose($handle);
        }

        return $a;
    }
}
