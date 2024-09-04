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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

\defined('_JEXEC') or die;

class ItemsModel extends ListModel
{
	public function getItems()
	{
		$config = Factory::getContainer()->get('config');
		$log_path = str_replace('\\', '/', $config->get('log_path'));
		$items = glob($log_path . '/*.*');

		foreach ($items as $i => &$item) {
			$item = basename($item);
			if ($item == 'index.html') {
				unset($items[$i]);
			}
		}
		$items = array_values($items);

		$phpErrorLog = ini_get('error_log');
		if ($phpErrorLog && file_exists($phpErrorLog)) {
			$items[] = 'PHP error log';
		}

		return $items;
	}
}
