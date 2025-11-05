<?php
/**
 * @package       View logs
 * @version       2.2.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2025 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\View\Items;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Button\CustomButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

use function defined;

defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    public $items;

    public function display($tpl = null)
    {
        $model       = $this->getModel();
        $this->items = $model->getItems();

        $this->getDocument()->getWebAssetManager()->useScript('core');

        ToolbarHelper::title(Text::_('COM_VLOGS'), 'health');
        /** @var Toolbar $toolbar */
        $app     = Factory::getApplication();
        $toolbar = $app->getDocument()->getToolbar();
        if (count($this->items)) {
            $filesearch_input = new CustomButton('onpage-file-search');
            $filesearch_input->html(
                '
                <input name="logfilesearch" id="logfilesearch" class="form-control w-50" placeholder="' . Text::_(
                    'JSEARCH_FILTER'
                ) . '"/>
                '
            );
            $toolbar->appendButton($filesearch_input);
        }


        $canDo = ContentHelper::getActions('com_vlogs');
        if ($canDo->get('core.admin')) {
            ToolBarHelper::preferences('com_vlogs');
        }

        parent::display($tpl);
    }
}
