<?php
/**
 * @package       View logs
 * @version       2.1.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2025 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Filesystem\File;

\defined('_JEXEC') or die;

class ItemModel extends BaseModel
{
	private static string $EOLPlaceholder = '<<<ViewLogsEOL>>>';


	protected function populateState()
	{
		$config = Factory::getContainer()->get('config');
		$log_path = str_replace('\\', '/', $config->get('log_path'));
		$this->setState('log.path', $log_path);

		$filename = Factory::getApplication()->getInput()->get('filename','');
		$this->setState('log.filename', $filename);

	}
	public function getItem(string $filename = ''): array
	{
		$filename = (!empty($filename)) ? $filename : (string) $this->getState('log.filename');
		if(empty($filename)) {
			return [];
		}

		$log_path = $this->getState('log.path');

		if(!file_exists( $log_path. DIRECTORY_SEPARATOR . $filename)) {
			throw new \RuntimeException("File '$filename' does not exist");
		}

		$item = $this->parseLogFile($filename);
		foreach ($item as &$row) {
			if(array_key_exists('message', $row) && str_contains($row['message'], self::$EOLPlaceholder))
			{
				$row['message'] = str_replace(self::$EOLPlaceholder,PHP_EOL, $row['message']);
			}
		}

		return $item;
	}

	private function getPhpLog()
	{
		$a = [];

		if (($handle = fopen(ini_get('error_log'), 'r')) !== false)
		{
			while (!feof($handle))
			{
				$data = fgets($handle);
				if ($data !== false)
				{
					$a[] = $data;
				}
			}
			fclose($handle);
		}

		$a = array_reverse($a);

		return $a;
	}

	/**
	 * Parse Joomla logs
	 *
	 * @param string $filename
	 *
	 * @return array
	 *
	 * @since 2.0.0
	 */
	private function parseLogFile(string $filename):array
	{
		$log_path = $this->getState('log.path');
		$file = file_get_contents($log_path. DIRECTORY_SEPARATOR . $filename);
		$lines = explode("\n", $file);
		$result = [];

		// Line with table headers contains `#Fields`
		[$headerLineIndex, $headers] = $this->getLogHeaders($filename);

		$headersCount = count($headers);

		if ($headerLineIndex === null) {
			return $result;
		}
		$currentEntry = null;

		for ($i = $headerLineIndex + 1; $i < count($lines); $i++) {

			$line = trim($lines[$i]);
			if (empty($line)) {
				continue;
			}

			// Check if line starts from date string (format: 2025-09-22T17:32:43+00:00)
			if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}\t/', $line)) {

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

		return $result;
	}

	/**
	 * For CSV logs
	 *
	 * @param string $file
	 * @param string $delimiter
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	private function getCSV(string $file, string $delimiter = ';'):array
	{
		$a    = [];
		$slen = ComponentHelper::getParams('com_vlogs')->get('slen', 32768);

		if (($handle = fopen($file, 'r')) !== false)
		{
			while (!feof($handle))
			{
				$data = fgetcsv($handle, $slen, $delimiter);
				if ($data !== false)
				{
					$a[] = $data;
				}
			}
			fclose($handle);
		}

		return $a;
	}



	public function dwFile()
	{
		$log_path = str_replace('\\', '/', Factory::getContainer()->get('config')->get('log_path'));
		$file     = filter_input(INPUT_GET, 'filename');
		$bom      = (bool) filter_input(INPUT_GET, 'bom');
		$fpath    = str_replace('\\', '/', Factory::getContainer()->get('config')->get('tmp_path'));

		if ($file === 'PHP error log')
		{
			$data = [];
			$log  = $this->getPhpLog();
			foreach ($log as $item)
			{
				if (empty($item))
				{
					continue;
				}
				$tmp  = explode('] ', $item);
				$date = substr($tmp[0], 1, strlen($tmp[0]) - 1);
				$date = explode(' ', $date);
				$date = new \DateTime($date[0] . 'T' . $date[1], new \DateTimeZone($date[2]));
				$date = date_format($date, 'Y-m-d H:i:s');
				[$type, $msg] = explode(':  ', $tmp[1]);
				$data[] = [$date, $type, trim($msg)];
			}

			$fileName = pathinfo(ini_get('error_log'))['filename'];
		}
		else
		{
			$data    = $this->parseLogFile($file);
			$fileName = pathinfo($fpath . '/' . $file)['filename'];
		}

		$data = array_reverse($data);

		$file = $fpath . '/' . $fileName . '_' . HTMLHelper::_('date', time(), 'Y-m-d-H-i-s') . '.csv';

		$this->setCSV($file, $data, $bom ? ';' : ',', $bom);
		$this->file_force_download($file);
		unlink($file);

		exit;
	}

	private function setCSV($file, $data, $delimiter = ';', $bom = false)
	{
		if (($handle = fopen($file, 'w')) !== false)
		{
			if ($bom)
			{
				fwrite($handle, "\xEF\xBB\xBF");
			}
			foreach ($data as $item)
			{
				fputcsv($handle, $item, $delimiter, '"','\\', PHP_EOL);
			}
			fclose($handle);
		}
	}

	private function file_force_download($file)
	{
		set_time_limit(0);
		if (file_exists($file))
		{
			if (ob_get_level())
			{
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

			return (bool) readfile($file);
		}
		else
		{
			return false;
		}
	}

	public function deleteFile(string $filename):bool
	{
		$log_path = $this->getState('log.path');
		$result = File::delete($log_path . DIRECTORY_SEPARATOR . $filename);
		return $result;
	}

	public function ArchiveFile()
	{
		$apath        = ComponentHelper::getParams('com_vlogs')->get('apath', 'tmp');
		$delAfterArch = (int) ComponentHelper::getParams('com_vlogs')->get('delafterarch', 0);

		if (!$apath)
		{
			$this->printJson(Text::_('COM_VLOGS_ARCHIVEFILE_NO_FOLDER'), false);
		}

		$apath = str_replace('\\', '/', JPATH_ROOT . '/' . $apath);

		if (!is_dir($apath))
		{
			$this->printJson(Text::_('COM_VLOGS_ARCHIVEFILE_NO_EXISTS_FOLDER'), false);
		}

		$log_path = str_replace('\\', '/', Factory::getContainer()->get('config')->get('log_path'));
		$file     = filter_input(INPUT_GET, 'filename');

		if ($file !== 'PHP error log')
		{
			if (!extension_loaded('zip'))
			{
				$this->printJson(Text::_('COM_VLOGS_NO_PHPZIP'), false);
			}

			$zip = new \ZipArchive();

			$archFile = pathinfo($log_path . DIRECTORY_SEPARATOR . $file, PATHINFO_FILENAME) . '__' . date('Y-m-d_h-i-s') . '.zip';
			$archPath = $apath . '/' . $archFile;

			if ($zip->open($archPath, \ZipArchive::CREATE) !== true)
			{
				$this->printJson(Text::_('COM_VLOGS_ARCHIVEFILE_ERROR_CREATE'), false);
			}
			else
			{
				$zip->addFile($log_path . '/' . $file, $file);
				$zip->close();
			}

			$resultDel = 0;
			if ($delAfterArch)
			{
				$resultDel = unlink($log_path . '/' . $file);
			}

			$this->printJson(
				Text::sprintf('COM_VLOGS_ARCHIVEFILE_ALERT_' . (int) ($delAfterArch && $resultDel), $file, str_replace(str_replace('\\', '/', JPATH_ROOT), '', $archPath)),
				true,
				['del' => (int) ($delAfterArch && $resultDel)]
			);
		}
		else
		{
			$this->printJson(Text::_('COM_VLOGS_NO_ARCHIVE_PHP_LOG') . '   ' . $file, false);
		}
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
	public function getLogHeaders(string $filename = ''):array
	{
		$filename = (!empty($filename)) ? $filename : (string) $this->getState('log.filename');
		if(empty($filename)) {
			return [];
		}

		$log_path = $this->getState('log.path');

		if(!file_exists($filename = $log_path. DIRECTORY_SEPARATOR . $filename)) {
			throw new \RuntimeException("File '$filename' does not exist");
		}

		$headers = [];
		$headerLineIndex = null;

		$handle = fopen($filename, 'r');

		if ($handle === false) {
			throw new \RuntimeException("Cannot open file: " . $filename);
		}

		$index = 0;
		while (($line = fgets($handle)) !== false) {
			if (strpos($line, '#Fields:') === 0) {
				$headerLineIndex = $index;

				$headerLine = str_replace('#Fields:', '', $line);
				$headers = explode("\t", trim($headerLine));

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
}
