<?php
/**
 * @package       View logs
 * @version       2.0.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright     Copyright (c) 2019 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\View\Items;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Helper\ContentHelper;

class HtmlView extends BaseHtmlView
{
	public $items;

	public function display($tpl = null)
	{

		$this->items = $this->get('Items');

		$this->getDocument()->getWebAssetManager()->useScript('core');

		ToolbarHelper::title(Text::_('COM_VLOGS'), 'health');

		if (\count($this->items))
		{
			foreach ($this->items as $key => $value)
			{
				$this->items[$value] = $value;
				unset($this->items[$key]);
			}

			$custom_button_html = '<button id="view_refresh_file" type="button" class="btn btn-success"><span class="icon-refresh"></span> ' . Text::_('COM_VLOGS_REFRESH_BUTTON') . '</button>';
			ToolBar::getInstance('toolbar')->appendButton('Custom', $custom_button_html, 'custom');

			$custom_button_html = '<button id="view_download_file" type="button" class="btn btn-info"><span class="icon-download"></span> ' . Text::_('COM_VLOGS_DOWNLOAD_BUTTON') . '</button>';
			ToolBar::getInstance('toolbar')->appendButton('Custom', $custom_button_html, 'custom');

			$custom_button_html = '<button id="view_download_bom_file" type="button" class="btn btn-info"><span class="icon-download"></span> ' . Text::_('COM_VLOGS_DOWNLOAD_BOM_BUTTON') . '</button>';
			ToolBar::getInstance('toolbar')->appendButton('Custom', $custom_button_html, 'custom');

			$custom_button_html = '<button id="view_delete_file" type="button" class="btn btn-danger"><span class="icon-delete"></span> ' . Text::_('COM_VLOGS_DELETEFILE_BUTTON') . '</button>';
			ToolBar::getInstance('toolbar')->appendButton('Custom', $custom_button_html, 'custom');

			if (extension_loaded('zip'))
			{
				$custom_button_html = '<button id="view_archive_file" type="button" class="btn btn-info"><span class="icon-cube"></span> ' . Text::_('COM_VLOGS_ARCHIVEFILE_BUTTON') . '</button>';
				ToolBar::getInstance('toolbar')->appendButton('Custom', $custom_button_html, 'custom');
			}
		}

		$canDo = ContentHelper::getActions('com_vlogs');
		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_vlogs');
		}


		parent::display($tpl);
	}
}
