<?php
/**
 * @package       View logs
 * @version       2.1.0
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

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-3 row-cols-xxl-4 g-2">
    <?php
    foreach ($this->items as $full_filename => $file_data):

        $filename = basename($full_filename);
        ?>
        <div class="col">
            <div class="card h-100 log-item" style="min-height: 100px;">
                <div class="card-body d-flex flex-column position-relative">
                    <div class="log-item-icon position-absolute w-100 h-100">
                        <i class="fa-solid fa-circle-info fs-2 text-secondary opacity-50"></i>
                    </div>
                    <?php


                    $dialog = [
                        'popupType' => 'iframe',
                        'textHeader' => $filename . ': ' . $file_data['lines_count'] . ' ' . Text::plural(
                                'COM_VLOGS_ITEMS_LIST_ITEM_LINES_COUNT',
                                $file_data['lines_count']
                            ),
                    ];
                    ?>
                    <a href="index.php?option=com_vlogs&view=item&tmpl=component&filename=<?php
                    echo $filename; ?>"
                       class="stretched-link mb-4 h3"
                       data-joomla-dialog='<?php
                       echo json_encode($dialog); ?>'><?php
                        echo $filename; ?></a>
                    <?php
                    //dump($file_data);
                    ?>
                    <hr class="hr mt-auto"/>
                    <span class="d-flex justify-content-between mb-2">
                            <span>
                                <span class="badge bg-danger"><?php
                                    echo $file_data['size_formatted']; ?></span>
                                <?php
                                // More then 1MB
                                if (1048576 < $file_data['size']):?>
                                    <span class="badge bg-warning"><i class="fa-solid fa-triangle-exclamation"></i> <?php
                                        echo Text::_('COM_VLOGS_ITEMS_LIST_ITEM_LARGE_FILE_ALERT'); ?></span>
                                <?php
                                endif; ?>
                            </span>
                            <span class="badge bg-info"><?php
                                echo $file_data['lines_count']; ?><?php
                                echo Text::plural(
                                    'COM_VLOGS_ITEMS_LIST_ITEM_LINES_COUNT',
                                    $file_data['lines_count']
                                ); ?></span>
                        </span>
                    <span class="text-nowrap">
                            <span class="badge bg-primary"><?php
                                echo Text::_('JGLOBAL_CREATED'); ?></span>
                            <span class="badge bg-secondary"><?php
                                echo HTMLHelper::date($file_data['created_at'], Text::_('DATE_FORMAT_LC6')); ?></span>
                        </span>
                    <span class="text-nowrap">
                            <span class="badge bg-primary"><?php
                                echo Text::_('JGLOBAL_MODIFIED'); ?></span>
                            <span class="badge bg-secondary"><?php
                                echo HTMLHelper::date($file_data['updated_at'], Text::_('DATE_FORMAT_LC6')); ?></span>
                        </span>

                </div>
                <div class="card-footer">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info" data-task="item.download"
                                data-download-type="csv" data-log-filename="<?php
                        echo $filename; ?>"><i class="icon icon-download"></i> CSV
                        </button>
                        <button type="button" class="btn btn-sm btn-info" data-task="item.download"
                                data-download-type="csvbom" data-log-filename="<?php
                        echo $filename; ?>"><i class="icon icon-download"></i> <span
                                    class="text-nowrap">CSV <sup>Exel</sup></span></button>
                        <a href="index.php?option=com_vlogs&view=item&filename=<?php
                        echo $filename; ?>" class="btn btn-sm btn-primary" target="_blank" title="<?php
                        echo Text::_('JGLOBAL_OPENS_IN_A_NEW_WINDOW'); ?>"><span class="visually-hidden"><?php
                                echo Text::_('JGLOBAL_OPENS_IN_A_NEW_WINDOW'); ?></span></a>
                        <button type="button" class="btn btn-sm btn-warning" data-task="item.archive"
                                data-log-filename="<?php
                                echo $filename; ?>"><i class="fa-regular fa-file-zipper"></i> ZIP
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" data-task="item.delete"
                                data-log-filename="<?php
                                echo $filename; ?>"><i class="icon icon-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
    <?php
    endforeach; ?>
</div>