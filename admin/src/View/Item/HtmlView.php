<?php
/**
 * @package       View logs
 * @version       2.3.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2026 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\View\Item;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Button\StandardButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

use function defined;

class HtmlView extends BaseHtmlView
{
    public $item;

    public function display($tpl = null)
    {
        $app   = Factory::getApplication();
        $model = $this->getModel();
        $input = $app->getInput();

        $filename = $input->getString('filename');
        $jlog = $input->getInt('jlog');


        $this->getDocument()->getWebAssetManager()->useScript('core');
        ToolbarHelper::title(
            Text::sprintf('COM_VLOGS_ITEM_TOOLBAR_TITLE', $filename),
            'file'
        );

        $phpErrorLogShow = (int) ComponentHelper::getParams('com_vlogs')->get('showphplog', 1);
        if (!$jlog && !$phpErrorLogShow) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_VLOGS_NO_SHOW_PHP_LOG'), 'notice');
            $this->headers         = [];
            $this->headerLineIndex = 0;
            $this->item            = [];
        } else {
            [$headerLineIndex, $headers] = ($jlog === 0)
                ? $model->getPhpLogHeaders()
                : $model->getLogHeaders();

            $this->headers         = $headers;
            $this->headerLineIndex = $headerLineIndex;
            $this->item            = $model->getItem();
            /** @var Toolbar $toolbar */
            $toolbar = $app->getDocument()->getToolbar();

            $reload_btn = new StandardButton('reload-log-btn');
            $reload_btn->text(Text::_('COM_VLOGS_ITEM_TOOLBAR_REFRESH'))
                ->buttonClass('btn btn-primary')
                ->icon('icon-refresh')
                ->onclick('location.reload()');
            $toolbar->appendButton($reload_btn);

            $download_btn = new StandardButton('download-log-btn');
            $download_btn->text(Text::_('COM_VLOGS_ITEM_TOOLBAR_DOWNLOAD_BUTTON'))
                ->buttonClass('btn btn-success')
                ->icon('icon-download')
                ->onclick(
                    'document.adminForm.download_type.value=\'csv\';document.adminForm.task.value=\'item.download\';document.adminForm.submit();'
                );
            $toolbar->appendButton($download_btn);

            $download_csv_bom_btn = new StandardButton('download-csv-bom-log-btn');
            $download_csv_bom_btn->text(Text::_('COM_VLOGS_ITEM_TOOLBAR_DOWNLOAD_BOM_BUTTON'))
                ->buttonClass('btn btn-success')
                ->icon('icon-download')
                ->onclick(
                    'document.adminForm.download_type.value=\'csvbom\';document.adminForm.task.value=\'item.download\';document.adminForm.submit();'
                );
            $toolbar->appendButton($download_csv_bom_btn);

            $zip_btn = new StandardButton('zip-log-btn');
            $zip_btn->text(Text::_('COM_VLOGS_ITEM_TOOLBAR_ARCHIVEFILE_BUTTON'))
                ->buttonClass('btn btn-success')
                ->icon('icon-archive')
                ->onclick('document.adminForm.task.value=\'item.archive\';document.adminForm.submit();');
            if ($jlog === 0) {
                $zip_btn->attributes(array('disabled' => 'disabled'));
            }
            $toolbar->appendButton($zip_btn);

            if ($jlog === 1) {
                $toolbar->delete('item.delete', 'JTOOLBAR_DELETE_FROM_TRASH')
                    ->message('JGLOBAL_CONFIRM_DELETE');
            } else {
                $toolbar->delete('item.delete', 'JTOOLBAR_DELETE_FROM_TRASH')
                    ->message('JGLOBAL_CONFIRM_DELETE')->attributes(array('disabled' => 'disabled'));
            }
        }

        parent::display($tpl);
    }
}
