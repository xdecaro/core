<?php
/**
 * @package     xdecaro.Core
 * @subpackage  com_xdecarocore
 */

namespace xdecaro\Component\Core\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use xdecaro\Component\Core\Administrator\Extension\CoreComponent;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('xdecaro\\Component\\Core'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('xdecaro\\Component\\Core'));

        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                return new CoreComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class),
                    $container->get(MVCFactoryInterface::class)
                );
            }
        );
    }
};
