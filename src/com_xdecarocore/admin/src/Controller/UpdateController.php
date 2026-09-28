<?php
/**
 * @package     xdecaro.Core
 * @subpackage  com_xdecarocore
 */

namespace xdecaro\Component\Core\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Updater\Updater;
use Joomla\Database\DatabaseInterface;
use xdecaro\Component\Core\Administrator\Service\EcosystemService;
use xdecaro\Component\Core\Administrator\Service\UpdaterService;

final class UpdateController extends BaseController
{
    private const REDIRECT_URL = 'index.php?option=com_xdecarocore&view=dashboard&layout=updates';

    public function find(): void
    {
        $this->checkToken();
        $this->assertInstallerAccess();
        $this->loadInstallerLanguage();

        $this->refreshUpdates();

        $this->app->enqueueMessage(Text::_('COM_XDECAROCORE_UPDATE_CHECK_COMPLETE'), 'message');
        $this->setRedirect(Route::_(self::REDIRECT_URL, false));
    }

    public function rebuildSites(): void
    {
        $this->checkToken();
        $this->assertInstallerAccess();
        $this->loadInstallerLanguage();

        $model = $this->installerUpdatesitesModel();
        $model->rebuild();

        // Joomla rebuilds the update-site tables from installed extension manifests.
        // Refresh the updater cache immediately so the Core page reflects the rebuilt state.
        $this->refreshUpdates();

        $this->setRedirect(Route::_(self::REDIRECT_URL, false));
    }

    public function update(): void
    {
        $this->checkToken();
        $this->assertInstallerAccess();
        $this->loadInstallerLanguage();

        $updateId = $this->input->getInt('update_id', 0);
        if ($updateId <= 0 || !$this->isManagedUpdateId($updateId)) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $params = ComponentHelper::getComponent('com_installer')->getParams();
        $minimumStability = (int) $params->get('minimum_stability', Updater::STABILITY_STABLE);
        $model = $this->installerUpdateModel();
        $model->update([$updateId], $minimumStability);

        $this->setRedirect(Route::_(self::REDIRECT_URL, false));
    }

    private function loadInstallerLanguage(): void
    {
        // Joomla installer models enqueue COM_INSTALLER_* messages. Core executes
        // those models outside com_installer, so the administrator language domain
        // must be loaded explicitly before any updater action runs.
        $this->app->getLanguage()->load('com_installer', JPATH_ADMINISTRATOR);
    }

    private function refreshUpdates(): void
    {
        $params = ComponentHelper::getComponent('com_installer')->getParams();
        $cacheTimeout = 3600 * (int) $params->get('cachetimeout', 6);
        $minimumStability = (int) $params->get('minimum_stability', Updater::STABILITY_STABLE);
        $model = $this->installerUpdateModel();

        $model->purge();
        $disabledUpdateSites = $model->getDisabledUpdateSites();
        $model->findUpdates(0, $cacheTimeout, $minimumStability);

        if ($disabledUpdateSites) {
            $this->app->enqueueMessage(Text::_('COM_XDECAROCORE_UPDATE_SITES_DISABLED_WARNING'), 'warning');
        }
    }

    private function installerUpdateModel(): object
    {
        $component = $this->app->bootComponent('com_installer');
        $model = $component->getMVCFactory()->createModel('Update', 'Administrator', ['ignore_request' => true]);

        if (!is_object($model)) {
            throw new \RuntimeException(Text::_('COM_XDECAROCORE_UPDATER_UNAVAILABLE'));
        }

        return $model;
    }

    private function installerUpdatesitesModel(): object
    {
        $component = $this->app->bootComponent('com_installer');
        $model = $component->getMVCFactory()->createModel('Updatesites', 'Administrator', ['ignore_request' => true]);

        if (!is_object($model) || !method_exists($model, 'rebuild')) {
            throw new \RuntimeException(Text::_('COM_XDECAROCORE_UPDATER_UNAVAILABLE'));
        }

        return $model;
    }

    private function assertInstallerAccess(): void
    {
        if (!$this->app->getIdentity()->authorise('core.manage', 'com_installer')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    private function isManagedUpdateId(int $updateId): bool
    {
        $container = Factory::getContainer();
        $db = $container->get(DatabaseInterface::class);
        $snapshot = (new EcosystemService($db))->snapshot();
        $updateIds = (new UpdaterService($db))->getUpdateIds($snapshot['products'] ?? []);

        return in_array($updateId, $updateIds, true);
    }
}
