<?php
/**
 * @package       View logs
 * @version       2.0.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright     Copyright (c) 2019 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') or die;

class DisplayController extends BaseController
{
	function display($cachable = false, $urlparams = [])
	{
		$this->default_view = 'items';
		parent::display($cachable, $urlparams);
	}

	public function getAjax()
	{
		$model = $this->getModel('ajax');
		$action = $this->input->getWord('action');
		$reflection = new \ReflectionClass($model);
		$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
		$methodList = [];
		foreach ($methods as $method) {
			$methodList[] = $method->name;
		}
		if (\in_array($action, $methodList)) {
			$model->$action();
		}
		exit;
	}
}
