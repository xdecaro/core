<?php
/**
 * @package     Xdecaro.Core
 * @subpackage  Plugin.System
 *
 * @copyright   Copyright (C) 2026 Luca De Caro
 * @license     GNU General Public License version 2 or later
 */

namespace Xdecaro\Plugin\System\XdecaroCore\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Lightweight Core bootstrap plugin.
 *
 * Version 1.0.0 intentionally registers no global listeners. Shared services
 * and assets must be loaded explicitly by consumers so Core adds effectively
 * no per-request work until a real cross-extension bootstrap need exists.
 */
final class XdecaroCore extends CMSPlugin
{
}
