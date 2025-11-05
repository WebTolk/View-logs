<?php
/**
 * @package       View logs
 * @version       2.2.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2025 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\View\Item;

defined('_JEXEC') or die;

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
        [$headerLineIndex, $headers] = $model->getLogHeaders();

        $this->headers         = $headers;
        $this->headerLineIndex = $headerLineIndex;
        $this->item            = $model->getItem();
        $this->getDocument()->getWebAssetManager()->useScript('core');

        $filename = $app->getInput()->getString('filename');
        ToolbarHelper::title(
            Text::sprintf('COM_VLOGS_ITEM_TOOLBAR_TITLE', $filename),
            'file'
        );
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
        $toolbar->appendButton($zip_btn);

        $toolbar->delete('item.delete', 'JTOOLBAR_DELETE_FROM_TRASH')
            ->message('JGLOBAL_CONFIRM_DELETE');

        parent::display($tpl);
    }
}
