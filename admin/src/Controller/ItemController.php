<?php
/**
 * @package       View logs
 * @version       2.1.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2025 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;

\defined('_JEXEC') or die;

class ItemController extends BaseController
{
	function display($cachable = false, $urlparams = [])
	{
		$this->default_view = 'item';
		parent::display($cachable, $urlparams);
	}

	public function delete()
	{
		if(!$this->checkToken()) {
			throw new \Exception(Text::_('JINVALID_TOKEN'), 500);
		}
		$model = $this->getModel('Item');
		$filename = $this->input->getString('filename');
		$ajax = $this->input->getBool('ajax', false);
		if(!$ajax) {
			$this->setRedirect('index.php?option=com_vlogs&view=items');
		}
		if($filename == 'PHP error log') {
			$this->setMessage(Text::_('COM_VLOGS_NO_DELETE_PHP_LOG') . ' ' . $filename, false);
			$this->redirect();
		}
		$result = $model->deleteFile($filename);
		$message = $result ? Text::sprintf('COM_VLOGS_DELETEFILE_SUCCESS', $filename) : Text::_('COM_VLOGS_DELETEFILE_ALERT');
		$message_type = $result ? 'success' : 'error';
		if($ajax) {
			echo new JsonResponse(null, $message, !$result);
			exit();
		} else {
			$this->setMessage($message, $message_type);
		}
	}
}
