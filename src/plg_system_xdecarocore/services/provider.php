<?php
/**
 * @package     xdecaro.Core
 * @subpackage  Plugin.System
 *
 * @copyright   Copyright (C) 2026 Luca De Caro
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use xdecaro\Plugin\System\XdecaroCore\Extension\XdecaroCore;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            static function (Container $container): XdecaroCore {
                return new XdecaroCore(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'xdecarocore')
                );
            }
        );
    }
};
