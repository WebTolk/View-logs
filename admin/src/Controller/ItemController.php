<?php
/**
 * @package       View logs
 * @version       2.3.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2026 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\Controller;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;

use function defined;

defined('_JEXEC') or die;

class ItemController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $this->default_view = 'item';
        parent::display($cachable, $urlparams);
    }

    public function delete()
    {
        if (!$this->checkToken()) {
            throw new Exception(Text::_('JINVALID_TOKEN'), 500);
        }
        $model    = $this->getModel('Item');
        $filename = $this->input->getString('filename');
        $jlog = $this->input->getInt('jlog');
        $ajax     = $this->input->getBool('ajax', false);
        if (!$ajax) {
            $this->setRedirect('index.php?option=com_vlogs&view=items');
        }
        if ($filename == 'PHP error log' || $jlog === 0) {
            $this->setMessage(Text::_('COM_VLOGS_NO_DELETE_PHP_LOG') . ' ' . $filename, false);
            $this->redirect();
        }
        $result       = $model->deleteFile($filename);
        $message      = $result ? Text::sprintf('COM_VLOGS_DELETEFILE_SUCCESS', $filename) : Text::_(
            'COM_VLOGS_DELETEFILE_ALERT'
        );
        $message_type = $result ? 'success' : 'error';
        if ($ajax) {
            if ( $jlog === 0 && !$result ) {
                echo new JsonResponse(null, Text::_('COM_VLOGS_NO_DELETE_PHP_LOG') . ' ' . $filename, !$result);
            } else {
                echo new JsonResponse(null, $message, !$result);
            }
            exit();
        } else {
            $this->setMessage($message, $message_type);
        }
    }

    /**
     * Download log file
     *
     * @throws Exception
     * @since 2.1.0
     */
    public function download()
    {
        if (!$this->checkToken()) {
            throw new Exception(Text::_('JINVALID_TOKEN'), 500);
        }

        $model = $this->getModel('Item');

        $filename     = $this->input->getString('filename');
        $jlog         = $this->input->getInt('jlog');
        $download_url = $model->downloadFile($filename, $jlog);
        $ajax         = $this->input->getBool('ajax', false);
        if ($ajax) {
            echo new JsonResponse(
                [
                    'download_url' => $download_url,
                    'filename'     => basename($download_url)
                ]
            );
            exit();
        }
    }

    /**
     * Download log file
     *
     * @throws Exception
     * @since 2.1.0
     */
    public function archive()
    {
        if (!$this->checkToken()) {
            throw new Exception(Text::_('JINVALID_TOKEN'), 500);
        }

        $model    = $this->getModel('Item');
        $filename = $this->input->getString('filename');
        $jlog     = $this->input->getInt('jlog');

        $archiveFileData = $model->archiveFile($filename, $jlog);
        $ajax            = $this->input->getBool('ajax', false);

        if ($ajax) {
            echo new JsonResponse(null, $archiveFileData['message'], !$archiveFileData['result']);
            exit();
        } else {
            $this->setMessage($archiveFileData['message'], $archiveFileData['result'] ? 'success' : 'danger');
            $this->setRedirect('index.php?option=com_vlogs&view=item&filename=' . $filename . '&jlog=' . $jlog);
            $this->redirect();
        }
    }
}
