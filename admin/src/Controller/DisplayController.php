<?php
/**
 * @package       View logs
 * @version       2.2.0.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (c) 2019 - 2025 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Component\Vlogs\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

use function defined;

defined('_JEXEC') or die;

class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $this->default_view = 'items';
        parent::display($cachable, $urlparams);
    }
}
