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
use Joomla\CMS\Router\Route;
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
    public $canManageInstaller = false;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $identity = $app->getIdentity();

        if (!$identity->authorise('core.manage', 'com_xdecarocore')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $container = Factory::getContainer();
        $service = new EcosystemService($container->get(DatabaseInterface::class));
        $this->snapshot = $service->snapshot();
        $this->coreVersion = Version::VERSION;
        $this->joomlaVersion = defined('JVERSION') ? JVERSION : '';
        $this->phpVersion = PHP_VERSION;
        $this->canManageInstaller = $identity->authorise('core.manage', 'com_installer');

        $webAssets = $this->getDocument()->getWebAssetManager();
        (new AssetService())->useComponents($webAssets);

        // Dashboard assets are required on every layout. The responsive layer is intentionally
        // separate so the base visual contract remains stable while Core adapts to Atum's
        // variable administrator sidebar and real mobile safe areas.
        $webAssets->getRegistry()->addExtensionRegistryFile('com_xdecarocore');
        $webAssets->useStyle('com_xdecarocore.admin');
        $webAssets->useStyle('com_xdecarocore.responsive');
        $webAssets->useScript('com_xdecarocore.admin');

        $titles = [
            'default' => 'COM_XDECAROCORE_DASHBOARD',
            'products' => 'COM_XDECAROCORE_PRODUCTS',
            'extensions' => 'COM_XDECAROCORE_EXTENSIONS',
            'updates' => 'COM_XDECAROCORE_UPDATES',
            'diagnostics' => 'COM_XDECAROCORE_DIAGNOSTICS',
            'information' => 'COM_XDECAROCORE_INFORMATION',
            'guide' => 'COM_XDECAROCORE_GUIDE',
        ];
        $layout = $this->getLayout() ?: 'default';

        ToolbarHelper::title(Text::_($titles[$layout] ?? 'COM_XDECAROCORE_DASHBOARD'), 'grid-2');

        // The suite Dashboard is the navigation reference point for every secondary Core view.
        if ($layout !== 'default') {
            ToolbarHelper::back(
                Text::_('JTOOLBAR_BACK'),
                Route::_('index.php?option=com_xdecarocore&view=dashboard', false)
            );
        }

        // Keep the user guide in Joomla's native toolbar instead of adding custom page chrome.
        if ($layout !== 'guide') {
            ToolbarHelper::link(
                Route::_('index.php?option=com_xdecarocore&view=dashboard&layout=guide', false),
                Text::_('COM_XDECAROCORE_GUIDE'),
                'help'
            );
        }

        // Keep configuration in Joomla's native toolbar. At the moment this exposes component
        // permissions and provides a stable place for future Core options without custom chrome.
        if ($identity->authorise('core.admin', 'com_xdecarocore')) {
            ToolbarHelper::preferences('com_xdecarocore');
        }

        parent::display($tpl);
    }
}
