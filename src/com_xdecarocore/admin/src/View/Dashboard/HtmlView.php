<?php
/**
 * @package     xdecaro.Core
 * @subpackage  com_xdecarocore
 */

namespace xdecaro\Component\Core\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use xdecaro\Component\Core\Administrator\Service\EcosystemService;
use xdecaro\Core\Asset\AssetService;
use xdecaro\Core\Version;

final class HtmlView extends BaseHtmlView
{
    public $snapshot = [];
    public $coreVersion = '';
    public $joomlaVersion = '';
    public $phpVersion = '';

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_xdecarocore')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $container = Factory::getContainer();
        $service = new EcosystemService($container->get(DatabaseInterface::class));
        $this->snapshot = $service->snapshot();
        $this->coreVersion = Version::VERSION;
        $this->joomlaVersion = defined('JVERSION') ? JVERSION : '';
        $this->phpVersion = PHP_VERSION;

        $webAssets = $this->getDocument()->getWebAssetManager();
        (new AssetService())->useComponents($webAssets);
        $webAssets->getRegistry()->addExtensionRegistryFile('com_xdecarocore');
        if ($webAssets->assetExists('style', 'com_xdecarocore.admin')) {
            $webAssets->useStyle('com_xdecarocore.admin');
        }

        $titles = [
            'default' => 'COM_XDECAROCORE_DASHBOARD',
            'products' => 'COM_XDECAROCORE_PRODUCTS',
            'extensions' => 'COM_XDECAROCORE_EXTENSIONS',
            'updates' => 'COM_XDECAROCORE_UPDATES',
            'diagnostics' => 'COM_XDECAROCORE_DIAGNOSTICS',
            'information' => 'COM_XDECAROCORE_INFORMATION',
        ];
        $layout = $this->getLayout() ?: 'default';
        ToolbarHelper::title(Text::_($titles[$layout] ?? 'COM_XDECAROCORE_DASHBOARD'), 'grid-2');

        parent::display($tpl);
    }
}
