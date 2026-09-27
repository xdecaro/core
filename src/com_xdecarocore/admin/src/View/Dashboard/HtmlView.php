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
use xdecaro\Component\Core\Administrator\Service\UpdaterService;
use xdecaro\Core\Asset\AssetService;
use xdecaro\Core\Version;

final class HtmlView extends BaseHtmlView
{
    public $snapshot = [];
    public $coreVersion = '';
    public $joomlaVersion = '';
    public $phpVersion = '';
    public $canManageInstaller = false;
    public $updateIds = [];
    public $updateSites = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $identity = $app->getIdentity();

        if (!$identity->authorise('core.manage', 'com_xdecarocore')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $container = Factory::getContainer();
        $db = $container->get(DatabaseInterface::class);
        $service = new EcosystemService($db);
        $this->snapshot = $service->snapshot();
        $this->coreVersion = Version::VERSION;
        $this->joomlaVersion = defined('JVERSION') ? JVERSION : '';
        $this->phpVersion = PHP_VERSION;
        $this->canManageInstaller = $identity->authorise('core.manage', 'com_installer');

        $updaterService = new UpdaterService($db);
        $this->updateIds = $updaterService->getUpdateIds($this->snapshot['products'] ?? []);
        $this->updateSites = $updaterService->getUpdateSites($this->snapshot['products'] ?? []);

        $webAssets = $this->getDocument()->getWebAssetManager();
        (new AssetService())->useAdminUi($webAssets);

        // Core uses the same public administrator UI contract as external consumers.
        // Component media now contains only Core-dashboard-specific styling and behavior.
        $webAssets->getRegistry()->addExtensionRegistryFile('com_xdecarocore');
        $webAssets->useStyle('com_xdecarocore.admin');
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

        if ($layout !== 'default') {
            ToolbarHelper::back(
                Text::_('JTOOLBAR_BACK'),
                Route::_('index.php?option=com_xdecarocore&view=dashboard', false)
            );
        }

        if ($layout === 'updates' && $this->canManageInstaller) {
            ToolbarHelper::custom(
                'update.find',
                'refresh',
                'refresh',
                Text::_('COM_XDECAROCORE_CHECK_UPDATES'),
                false
            );

            ToolbarHelper::custom(
                'update.rebuildSites',
                'refresh',
                'refresh',
                Text::_('COM_XDECAROCORE_REBUILD_UPDATE_SITES'),
                false
            );

            ToolbarHelper::link(
                Route::_('index.php?option=com_installer&view=updatesites', false),
                Text::_('COM_XDECAROCORE_MANAGE_UPDATE_SITES'),
                'cog'
            );
        }

        if ($layout !== 'guide') {
            ToolbarHelper::link(
                Route::_('index.php?option=com_xdecarocore&view=dashboard&layout=guide', false),
                Text::_('COM_XDECAROCORE_GUIDE'),
                'help'
            );
        }

        if ($identity->authorise('core.admin', 'com_xdecarocore')) {
            ToolbarHelper::preferences('com_xdecarocore');
        }

        parent::display($tpl);
    }
}
