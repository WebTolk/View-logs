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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Filesystem\Folder;
use RuntimeException;

\defined('_JEXEC') or die;

class ItemsModel extends ListModel
{
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);
		$this->config = Factory::getContainer()->get('config');
		$this->log_path = str_replace('\\', '/', $this->config->get('log_path'));
	}

	public function getItems()
	{
		$files = Folder::files($this->log_path,'.php',true,true);



		$phpErrorLog = ini_get('error_log');
		if ($phpErrorLog && file_exists($phpErrorLog)) {
			$files[] = $phpErrorLog;
		}
		$items = [];
		foreach ($files as $file) {
			$items[$file] = $this->getFileMetadata($file);
		}

		return $items;
	}

    /**
     * Get filesize, date created etc.
     *
     * @param   string  $filename
     *
     * @return array
     *
     * @since 2.1.0
     */
    public function getFileMetadata(string $filename = ''):array
	{
		if (empty($filename)) {
			throw new \InvalidArgumentException('Filename cannot be empty');
		}

		if (!file_exists($filename)) {
            throw new RuntimeException("File '$filename' does not exist");
		}

		if (!is_readable($filename)) {
			throw new RuntimeException("File '$filename' is not readable");
		}

		$metadata = [];

		// Размер файла
		$metadata['size'] = filesize($filename);
		$metadata['size_formatted'] = $this->formatFileSize($metadata['size']);

		// Количество строк
		$linesCount = 0;
		$handle = fopen($filename, 'r');
		if ($handle) {
			while (!feof($handle)) {
				fgets($handle);
				$linesCount++;
			}
			fclose($handle);
		}
		$metadata['lines_count'] = $linesCount;

		$fileInfo = stat($filename);
		if ($fileInfo !== false) {
			$metadata['created_at'] = date('Y-m-d H:i:s', $fileInfo['ctime']);
			$metadata['updated_at'] = date('Y-m-d H:i:s', $fileInfo['mtime']);
		} else {
			$metadata['created_at'] = null;
			$metadata['updated_at'] = null;
		}

//		$metadata['file_type'] = filetype($filename);
//		$metadata['is_readable'] = is_readable($filename);
//		$metadata['is_writable'] = is_writable($filename);
		$metadata['real_path'] = realpath($filename);

		return $metadata;
	}


    /**
     * Format file size to human friendly
     *
     * @param   int  $bytes
     * @param   int  $precision
     *
     * @return string
     *
     * @since 2.1.0
     */
    private function formatFileSize(int $bytes, int $precision = 2): string
	{
		if ($bytes == 0) {
			return '0 B';
		}

		$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

		$base = 1024;
		$exponent = (int) floor(log($bytes, $base));
		$exponent = min($exponent, count($units) - 1);

		$formattedSize = $bytes / pow($base, $exponent);

		return round($formattedSize, $precision) . ' ' . $units[$exponent];
	}
}
