<?php
/**
 * @package       View logs
 * @version       2.0.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright     Copyright (c) 2019 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;

\defined('_JEXEC') or die;

class AjaxModel extends ListModel
{
	public function List()
	{
		$log_path = str_replace('\\', '/', Factory::getContainer()->get('config')->get('log_path'));
		$file     = (string) filter_input(INPUT_GET, 'filename');
		if ($file === 'PHP error log')
		{
			$this->ListPHPEL();
		}
		if ($file == '')
		{
			$html = '<div class="alert alert-primary">' . Text::_('COM_VLOGS_LIST_EMPTY') . '</div>';
			$this->printJson($html, true, ['count' => 0]);
		}

		$columns = '';
		$data    = $this->getCSV($log_path . '/' . $file, '	');
		for ($i = 0; $i < 6; $i++)
		{
			if (count($data[$i]) < 4 || $data[$i][0][0] == '#')
			{
				if (!empty($data[$i][0]) && strpos($data[$i][0], '#Fields:') !== false)
				{
					$columns = $data[$i];
				}
				unset($data[$i]);
			}
		}
		if ($columns)
		{
			$columns = explode(' ', implode(' ', $columns));
			unset($columns[0]);
			$columns = array_values($columns);
		}

		$data = array_reverse($data);
		$html = [];
		$cnt  = count($data);

		if ($columns && $cnt)
		{
			$html[] = '<table class="com_vlogs table table-striped"><thead><tr>';

			foreach ($columns as $col)
			{
				switch ($col)
				{
					case 'datetime':
						$html[] = '<th width="10%">' . Text::_('COM_VLOGS_COLUMN_DT') . '</th>';
						break;
					case 'date':
						$html[] = '<th width="5%">' . Text::_('COM_VLOGS_COLUMN_DATE') . '</th>';
						break;
					case 'time':
						$html[] = '<th width="5%">' . Text::_('COM_VLOGS_COLUMN_TIME') . '</th>';
						break;
					case 'priority':
						$html[] = '<th width="5%">' . Text::_('COM_VLOGS_COLUMN_PRIORITY') . '</th>';
						break;
					case 'clientip':
						$html[] = '<th width="5%">' . Text::_('COM_VLOGS_COLUMN_IP') . '</th>';
						break;
					case 'category':
						$html[] = '<th width="5%">' . Text::_('COM_VLOGS_COLUMN_CATEGORY') . '</th>';
						break;
					case 'message':
						$cw = (count($columns) - 1) * 5;
						if (in_array('datetime', $columns)) $cw += 5;
						$html[] = '<th width="' . (100 - $cw) . '%">' . Text::_('COM_VLOGS_COLUMN_MSG') . '</th>';
						break;
					default:
						$html[] = '<th>' . $col . '</th>';
				}
			}

			$html[] = '</tr></thead><tbody>';

			foreach ($data as $i => $item)
			{
				if (count($item) == 1)
				{
					$html[] = '<tr class="row' . ($i % 2) . '">';
					$html[] = str_repeat('<td></td>', count($columns) - 1);
					$html[] = '<td>' . $item[0] . '</td>';
					$html[] = '</tr>';
					continue;
				}

				if (count($item) < count($columns))
				{
					$ci  = count($item) - 1;
					$msg = $item[$ci];
					unset($item[$ci]);
					$item   = explode(' ', implode(' ', $item));
					$item[] = $msg;
					unset($msg);
				}

				$html[] = '<tr class="row' . ($i % 2) . '">';
				foreach ($item as $j => $dataitem)
				{
					switch (strtolower($columns[$j]))
					{
						case 'datetime':
							try
							{
								$date     = new \DateTime($dataitem);
								$dataitem = $date->format('U');
								$date     = HTMLHelper::_('date', $dataitem, 'Y-m-d H:i:s');
							}
							catch (\Exception $e)
							{
								$date = '';
							}
							$html[] = '<td class="nowrap">' . $date . '</td>';
							break;
						case 'priority':
							switch (strtolower($dataitem))
							{
								case 'emergency':
									$html[] = '<td class="text-error">' . $dataitem . '</td>';
									break;
								case 'alert':
									$html[] = '<td class="text-warning">' . $dataitem . '</td>';
									break;
								case 'critical':
									$html[] = '<td class="text-error">' . $dataitem . '</td>';
									break;
								case 'error':
									$html[] = '<td class="text-error">' . $dataitem . '</td>';
									break;
								case 'warning':
									$html[] = '<td class="text-warning">' . $dataitem . '</td>';
									break;
								case 'notice':
									$html[] = '<td class="text-info">' . $dataitem . '</td>';
									break;
								case 'info':
									$html[] = '<td class="text-info">' . $dataitem . '</td>';
									break;
								case 'debug':
									$html[] = '<td class="text-info">' . $dataitem . '</td>';
									break;
								default:
									$html[] = '<td>' . $dataitem . '</td>';
							}
							break;
						case 'message':
							$json        = json_decode($dataitem, true);
							$json_result = json_last_error() === JSON_ERROR_NONE;
							$html[]      = '<td>' . ($json_result ? '<div><a href="javascript:void(0)" onclick="jQuery(this).parent().next(\'pre\').slideToggle(200);" style="cursor:pointer">' . Text::_('COM_VLOGS_COLUMN_MSG_JSON_TITLE') . '</a></div><pre style="display:none">' . print_r($json, true) . '</pre>' : htmlspecialchars($dataitem)) . '</td>';
							break;
						default:
							$html[] = '<td>' . $dataitem . '</td>';
					}
				}
				$html[] = '</tr>';
			}

			$html[] = '</tbody></table>';
		}
		else
		{
			$html[] = '<div class="alert alert-primary">' . Text::_('COM_VLOGS_DATA_EMPTY') . '</div>';
		}

		$this->printJson(implode('', $html), true, ['count' => $cnt]);
	}

	protected function ListPHPEL()
	{
		$data = $this->getPhpLog();
		$cnt  = 0;
		$html = [];

		if (!empty($data))
		{
			$html[] = '<table class="com_vlogs table table-striped"><thead><tr>';
			$html[] = '<th width="10%">' . Text::_('COM_VLOGS_COLUMN_DT') . '</th>';
			$html[] = '<th width="10%">' . Text::_('COM_VLOGS_COLUMN_PRIORITY') . '</th>';
			$html[] = '<th width="80%">' . Text::_('COM_VLOGS_COLUMN_MSG') . '</th>';
			$html[] = '</tr></thead><tbody>';

			foreach ($data as $item)
			{
				if (empty($item))
				{
					continue;
				}
				$tmp = explode('] ', $item);
				try
				{
					if ($tmp[0][0] == '[')
					{
						$date = substr($tmp[0], 1, strlen($tmp[0]) - 1);
						$date = explode(' ', $date);
						$date = new \DateTime($date[0] . 'T' . $date[1], new \DateTimeZone($date[2]));
						$date = date_format($date, 'Y-m-d H:i:s');
					}
					else
					{
						$date = '';
					}
				}
				catch (\Exception $e)
				{
					$date = '';
				}
				if ($date && \count($tmp) > 0)
				{
						[$type, $msg] = explode(':  ', $tmp[1]);
				}
				else
				{
					$type = '';
					$msg  = $item;
				}
				$html[] = '<tr>';
				$html[] = '<td>' . $date . '</td>';
				switch ($type)
				{
					case 'PHP Error':
					case 'PHP Fatal error':
						$html[] = '<td class="text-error">' . $type . '</td>';
						break;
					case 'PHP Warning':
						$html[] = '<td class="text-warning">' . $type . '</td>';
						break;
					case 'PHP Notice':
						$html[] = '<td class="text-info">' . $type . '</td>';
						break;
					default:
						$html[] = '<td>' . $type . '</td>';
				}
				$html[] = '<td>' . $msg . '</td>';
				$html[] = '</tr>';
				$cnt++;
			}
			$html[] = '</tbody></table>';
		}
		else
		{
			$html[] = '<div class="alert alert-primary">' . Text::_('COM_VLOGS_DATA_EMPTY') . '</div>';
		}

		$this->printJson(implode('', $html), true, ['count' => $cnt]);
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

	private function printJson($message, $result = false, $custom = [])
	{
		if (empty($message))
		{
			$message = '< empty message >';
		}

		$jsonData = ['result' => $result, 'message' => $message];

		foreach ($custom as $key => $value)
		{
			$jsonData[$key] = $value;
		}

		echo json_encode($jsonData);

		exit;
	}

	private function getCSV($file, $delimiter = ';')
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
			$data    = $this->getCSV($log_path . '/' . $file, '	');
			$base_ci = -1;
			foreach ($data as $i => $item)
			{
				if ($i < 6 && (count($item) < 4 || $item[0][0] == '#'))
				{
					if (strpos($item[0], '#Fields:') !== false)
					{
						$item[0] = str_replace('#Fields: ', '', $item[0]);
						foreach ($item as $l => $fname)
						{
							if (strtolower($fname) === 'message')
							{
								$base_ci = $l;
								break;
							}
						}
					}
					unset($data[$i]);
				}
				else
				{
					if (count($item) == 1)
					{
						$item = explode(' ', $item[0]);
					}
					else
					{
						$ci  = $base_ci >= 0 ? $base_ci : count($item) - 1;
						$msg = $item[$ci];
						unset($item[$ci]);
						$item   = explode(' ', implode(' ', $item));
						$item[] = '"' . $msg . '"';
						unset($msg);
					}
					$data[$i] = $item;
				}
			}

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
				fputcsv($handle, $item, $delimiter);
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

	public function DelFile()
	{
		$log_path = str_replace('\\', '/', Factory::getContainer()->get('config')->get('log_path'));
		$file     = filter_input(INPUT_GET, 'filename');

		if ($file !== 'PHP error log')
		{
			$result = unlink($log_path . '/' . $file);
			$this->printJson($result ? Text::sprintf('COM_VLOGS_DELETEFILE_SUCCESS', $file) : Text::_('COM_VLOGS_DELETEFILE_ALERT'), $result);
		}
		else
		{
			$this->printJson(Text::_('COM_VLOGS_NO_DELETE_PHP_LOG') . '   ' . $file, false);
		}
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
}
