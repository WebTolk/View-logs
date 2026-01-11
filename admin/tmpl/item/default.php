<?php
/**
 * @package       View logs
 * @version       2.3.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2026 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;
//dump($this->headers);
//dump($this->item);

$lang_constants = [
    'datetime'     => 'COM_VLOGS_COLUMN_DT',
    'date'         => 'COM_VLOGS_COLUMN_DATE',
    'time'         => 'COM_VLOGS_COLUMN_TIME',
    'php_priority' => 'COM_VLOGS_COLUMN_PRIORITY',
    'priority'     => 'COM_VLOGS_COLUMN_PRIORITY',
    'clientip'     => 'COM_VLOGS_COLUMN_IP',
    'category'     => 'COM_VLOGS_COLUMN_CATEGORY',
    'message'      => 'COM_VLOGS_COLUMN_MSG',
];

$priority_constants = [
    'emergency'  => 'danger',
    'alert'      => 'danger',
    'critical'   => 'danger',
    'error'      => 'danger',
    'warning'    => 'warning',
    'notice'     => 'primary',
    'info'       => 'info',
    'debug'      => 'secondary',
    'deprecated' => 'secondary',
];

?>
<?php if ($this->headers || $this->item) { ?>
<table class="table table-striped table-hover">
    <thead class="sticky-top">
    <tr class="w-100">
        <?php
        foreach ($this->headers as $header): ?>

            <?php
            if ($header == 'priority clientip'): ?>
                <th width="10%">
                    <?php
                    echo Text::_($lang_constants['priority']); ?>
                </th>
                <th width="10%">
                    <?php
                    echo Text::_($lang_constants['clientip']); ?>
                </th>
            <?php
            else: ?>
                <th width="<?php
                echo ($header !== 'message') ? '10%' : ''; ?>">
                    <?php
                    // translation fix `in_array` -> `array_key_exists`
                    if (array_key_exists(strtolower($header), $lang_constants)) {
                        echo Text::_($lang_constants[strtolower($header)]);
                    } else {
                        echo $header;
                    }

                    ?></th>
            <?php
            endif; ?>
        <?php
        endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($this->item as $row): ?>
        <tr>
            <?php
            foreach ($row as $header => $td) :?>
                <td>
                    <?php
                    switch ($header) {
                        case 'datetime':
                            echo HTMLHelper::date($td, 'DATE_FORMAT_LC6');
                            break;
                        case 'php_priority':
                            $php_priority = strtolower($td);
                            $php_priority = trim(str_replace('php', '', $php_priority));
                            echo '<span class="badge ' . (array_key_exists(
                                    $php_priority,
                                    $priority_constants
                                    ) ? 'bg-' . $priority_constants[$php_priority] : '') . '">' . $td . '</span>';
                            break;
                        case 'priority':
                            $priority = strtolower($td);
                            echo '<span class="badge ' . (array_key_exists(
                                    strtolower($td),
                                    $priority_constants
                                ) ? 'bg-' . $priority_constants[$priority] : '') . '">' . $td . '</span>';
                            break;
                        case 'clientip':
                            echo '<code>' . $td . '</code>';
                            break;
                        case 'priority clientip':
                            $td       = explode(' ', $td);
                            $priority = strtolower($td[0]);
                            echo '<span class="badge ' . (array_key_exists(
                                    $priority,
                                    $priority_constants
                                ) ? 'bg-' . $priority_constants[$priority] : '') . '">' . $td[0] . '</span></td>';
                            echo '<td><code>' . ( $td[1] ?? '' ) . '</code>';
                            break;
                        default:
                            $json        = json_decode($td, true);
                            $json_result = json_last_error() === JSON_ERROR_NONE;
                            echo $json_result ? '<details><summary>' . Text::_(
                                    'COM_VLOGS_COLUMN_MSG_JSON_TITLE'
                                ) . '</summary><div class="p-2"><pre>' . print_r(
                                    $json,
                                    true
                                ) . '</pre></div></details>' : nl2br($td);
                            break;
                    }

                    ?>
                </td>
            <?php
            endforeach; ?>
        </tr>
    <?php
    endforeach; ?>
    </tbody>
</table>
<?php } ?>
<?php
$input    = Factory::getApplication()->getInput();
$filename = $input->getString('filename');
$jlog     = $input->getInt('jlog');
?>
<form action="<?php
echo Route::_('index.php?option=com_vlogs&view=item&filename=' . $filename . '&jlog=' . $jlog . '&format=row'); ?>" method="post" name="adminForm"
      id="adminForm" class="d-none">
    <input type="hidden" name="task" value="">
    <input type="hidden" name="download_type" value="csv"/>
    <?php
    echo HTMLHelper::_('form.token'); ?>
</form>
