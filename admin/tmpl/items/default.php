<?php
/**
 * @package       View logs
 * @version       2.0.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright     Copyright (c) 2019 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('jquery')->registerAndUseScript('com_vlogs.admin.js', 'com_vlogs/admin.js');

?>
<div class="row">
    <div class="col-12 col-md-4"> <?php
		echo HTMLHelper::_(
			'select.genericlist',
			$this->items,
			'view_select_files',
			'class="form-select"',
			'value',
			'text',
		);

		?></div>
    <div class="col-6 col-md-3">
        <div class="alert alert-success m-0 py-2"><?php echo Text::_('COM_VLOGS_COUNT_ITEMS_VIEW'); ?> &nbsp;<span
                    id="view_count_items" class="badge bg-success">0</span></div>
    </div>

</div>
<div class="main-card">
    <form action="<?php echo Route::_('index.php?option=com_vlogs&view=items'); ?>" method="post" name="adminForm"
          id="adminForm">
        <div id="j-main-container">
            <div id="view_items_list"></div>
            <input type="hidden" name="task" value=""/>
			<?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </form>

</div>