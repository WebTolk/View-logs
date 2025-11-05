<?php
/**
 * @package       View logs
 * @version       2.2.0.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2025 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_vlogs.style', 'com_vlogs/style.css')
    ->registerAndUseScript('com_vlogs.script', 'com_vlogs/admin.js')
    ->useScript('joomla.dialog')
    ->useScript('joomla.dialog-autocreate');
?>


<div class="main-card">
    <?php
    foreach ($this->items as $full_filename => $file_data):

        $filename = basename($full_filename);
        $is_large_file = (1048576 < $file_data['size']);
        ?>
        <div class="row border border-1 p-3 mx-0 position-relative log-item">
            <div class="col-12 col-sm-6 col-md-5 col-lg-3 position-relative">
                <?php  if(!$is_large_file): ?>
                    <div class="log-item-icon position-absolute w-100 h-100">
                        <i class="fa-solid fa-circle-info fs-2 text-secondary opacity-50"></i>
                    </div>
                <?php endif;?>
                <div class="d-flex">
                    <?php
                    if(!$is_large_file) {
                        $dialog = [
                            'popupType'  => 'iframe',
                            'textHeader' => $filename . ': ' . $file_data['lines_count'] . ' ' . Text::plural(
                                    'COM_VLOGS_ITEMS_LIST_ITEM_LINES_COUNT',
                                    $file_data['lines_count']
                                ),
                        ];
                        ?>
                        <a href="index.php?option=com_vlogs&view=item&tmpl=component&filename=<?php
                        echo $filename; ?>"
                           class="stretched-link h3 mb-lg-0 text-wrap"
                           data-joomla-dialog='<?php
                           echo json_encode($dialog); ?>'
                           data-log-filename="<?php echo $filename; ?>"><?php
                            echo $filename; ?>

                        </a>
                        <?php
                    } else {
                        ?>
                        <span class="h3 text-wrap" data-log-filename="<?php echo $filename; ?>"><?php echo $filename; ?></span>
                        <?php
                    }
                    ?>

                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-0 d-flex flex-lg-column align-items-center">
            <span class="text-nowrap me-1">
                            <small class="text-muted" title="<?php
                            echo Text::_('JGLOBAL_CREATED'); ?>" aria-label="<?php
                            echo Text::_('JGLOBAL_CREATED'); ?>">
                                <i class="icon icon-plus"></i>
                                <?php
                                echo HTMLHelper::date($file_data['created_at'], Text::_('DATE_FORMAT_LC6')); ?></small>
                        </span>
                <small class="text-nowrap">
                            <span title="<?php
                            echo Text::_('JGLOBAL_MODIFIED'); ?>" aria-label="<?php
                            echo Text::_('JGLOBAL_MODIFIED'); ?>">
                                <i class="icon icon-edit"></i>
                                <?php
                                echo HTMLHelper::date($file_data['updated_at'], Text::_('DATE_FORMAT_LC6')); ?></span>
                        </small>
            </div>
            <div class="col-12 col-sm col-md-6 col-lg-2 d-flex flex-lg-column  mb-2 mb-lg-0 align-items-center">

            <span class="text-nowrap">
                    <small class="text-muted me-1"><?php
                        echo $file_data['size_formatted']; ?></small>
                    <?php
                    if ($is_large_file):?>
                        <span class="badge bg-warning" title="<?php
                        echo Text::_('COM_VLOGS_ITEMS_LIST_ITEM_LARGE_FILE_ALERT'); ?>"><i
                                    class="fa-solid fa-triangle-exclamation"></i></span>
                    <?php
                    endif; ?>
                </span>
                <?php
                if (array_key_exists('lines_count', $file_data)) : ?>
                    <small><?php
                        echo $file_data['lines_count']; ?><?php
                        echo Text::plural(
                            'COM_VLOGS_ITEMS_LIST_ITEM_LINES_COUNT',
                            $file_data['lines_count']
                        ); ?></small>
                <?php
                endif; ?>

            </div>
            <div class="col-12 col-sm-12 col-lg-4 d-flex mb-2 mb-lg-0 align-items-center justify-content-lg-end">
                <div class="d-flex">
                    <button type="button" class="btn btn-sm btn-info me-1" data-task="item.download"
                            data-download-type="csv" data-log-filename="<?php
                    echo $filename; ?>"><i class="icon icon-download"></i> CSV
                    </button>
                    <button type="button" class="btn btn-sm btn-info me-1" data-task="item.download"
                            data-download-type="csvbom" data-log-filename="<?php
                    echo $filename; ?>"><i class="icon icon-download"></i> <span
                                class="text-nowrap">CSV <sup>Exel</sup></span></button>
                    <button type="button" class="btn btn-sm btn-info me-1" data-task="item.archive"
                            data-log-filename="<?php
                            echo $filename; ?>"><i class="fa-regular fa-file-zipper"></i> ZIP
                    </button>
                    <a href="index.php?option=com_vlogs&view=item&filename=<?php
                    echo $filename; ?>" class="btn btn-sm btn-primary me-1" target="_blank" title="<?php
                    echo Text::_('JGLOBAL_OPENS_IN_A_NEW_WINDOW'); ?>"><span class="visually-hidden"><?php
                            echo Text::_('JGLOBAL_OPENS_IN_A_NEW_WINDOW'); ?></span></a>
                    <button type="button" class="btn btn-sm btn-danger me-1" data-task="item.delete"
                            data-log-filename="<?php
                            echo $filename; ?>"><i class="icon icon-trash"></i></button>
                </div>
            </div>
        </div>
    <?php
    endforeach; ?>
</div>