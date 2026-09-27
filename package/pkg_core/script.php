<?php
/**
 * @package     xdecaro.Core
 * @subpackage  Package
 *
 * @copyright   Copyright (C) 2026 Luca De Caro
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class pkg_coreInstallerScript extends InstallerScript
{
    private const CANONICAL_PACKAGE = 'pkg_core';
    private const LEGACY_PACKAGE = 'pkg_xdecarocore';

    /** @var string */
    protected $minimumJoomla = '6.0.0';

    /** @var string */
    protected $minimumPhp = '8.3.0';

    public function postflight($type, $parent): void
    {
        if ($type === 'uninstall') {
            return;
        }

        $normalizationFailed = false;

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } catch (\Throwable $exception) {
            Log::add('Core package database service resolution failed: ' . $exception->getMessage(), Log::WARNING, self::CANONICAL_PACKAGE);
            $this->enqueueNormalizationWarning();
            return;
        }

        foreach (['enableCorePlugin', 'removeRetiredCompatibilityLibrary', 'repairAdministratorMenuLinks', 'retireLegacyPackageRegistration'] as $method) {
            try {
                $this->{$method}($db);
            } catch (\Throwable $exception) {
                Log::add('Core package normalization failed in ' . $method . ': ' . $exception->getMessage(), Log::WARNING, self::CANONICAL_PACKAGE);
                $normalizationFailed = true;
            }
        }

        if ($normalizationFailed) {
            $this->enqueueNormalizationWarning();
        }
    }

    private function enqueueNormalizationWarning(): void
    {
        Factory::getApplication()->getLanguage()->load('com_xdecarocore', JPATH_ADMINISTRATOR);
        Factory::getApplication()->enqueueMessage(Text::_('COM_XDECAROCORE_POSTFLIGHT_WARNING'), 'warning');
    }

    private function enableCorePlugin(DatabaseInterface $db): void
    {
        $enabled = 1;
        $pluginType = 'plugin';
        $folder = 'system';
        $element = 'xdecarocore';

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = :enabled')
            ->where($db->quoteName('type') . ' = :type')
            ->where($db->quoteName('folder') . ' = :folder')
            ->where($db->quoteName('element') . ' = :element')
            ->bind(':enabled', $enabled, ParameterType::INTEGER)
            ->bind(':type', $pluginType)
            ->bind(':folder', $folder)
            ->bind(':element', $element);

        $db->setQuery($query)->execute();
    }

    private function removeRetiredCompatibilityLibrary(DatabaseInterface $db): void
    {
        $libraryType = 'library';
        $libraryElement = 'xdecaro/corelegacy';

        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :libraryType')
            ->where($db->quoteName('element') . ' = :libraryElement')
            ->bind(':libraryType', $libraryType)
            ->bind(':libraryElement', $libraryElement);

        $extensionId = (int) $db->setQuery($query, 0, 1)->loadResult();
        if ($extensionId <= 0) {
            return;
        }

        $installer = new Installer();
        $installer->setDatabase($db);
        $installer->setPackageUninstall(true);

        if (!$installer->uninstall($libraryType, $extensionId)) {
            throw new \RuntimeException('Joomla did not remove extension ID ' . $extensionId . '.');
        }
    }

    private function repairAdministratorMenuLinks(DatabaseInterface $db): void
    {
        $componentType = 'component';
        $componentElement = 'com_xdecarocore';

        $componentQuery = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :componentType')
            ->where($db->quoteName('element') . ' = :componentElement')
            ->bind(':componentType', $componentType)
            ->bind(':componentElement', $componentElement);

        $componentId = (int) $db->setQuery($componentQuery, 0, 1)->loadResult();
        if ($componentId <= 0) {
            return;
        }

        $clientId = 1;
        $menuQuery = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('link')])
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('client_id') . ' = :clientId')
            ->where($db->quoteName('component_id') . ' = :componentId')
            ->bind(':clientId', $clientId, ParameterType::INTEGER)
            ->bind(':componentId', $componentId, ParameterType::INTEGER);

        $rows = $db->setQuery($menuQuery)->loadObjectList();
        foreach ($rows ?: [] as $row) {
            $link = (string) $row->link;
            $normalized = preg_replace('#^(?:index\.php\?)+(?=option=com_xdecarocore(?:&|$))#', 'index.php?', $link);
            if (!is_string($normalized) || $normalized === $link) {
                continue;
            }

            $menuId = (int) $row->id;
            $update = $db->getQuery(true)
                ->update($db->quoteName('#__menu'))
                ->set($db->quoteName('link') . ' = :link')
                ->where($db->quoteName('id') . ' = :menuId')
                ->bind(':link', $normalized)
                ->bind(':menuId', $menuId, ParameterType::INTEGER);
            $db->setQuery($update)->execute();
        }
    }

    private function retireLegacyPackageRegistration(DatabaseInterface $db): void
    {
        $canonicalId = $this->packageExtensionId($db, self::CANONICAL_PACKAGE);
        $legacyId = $this->packageExtensionId($db, self::LEGACY_PACKAGE);

        if ($canonicalId <= 0 || $legacyId <= 0 || $canonicalId === $legacyId) {
            return;
        }

        $children = [
            ['library', 'xdecaro/core', null],
            ['component', 'com_xdecarocore', null],
            ['plugin', 'xdecarocore', 'system'],
        ];

        foreach ($children as [$type, $element, $folder]) {
            $packageId = $this->childPackageId($db, $type, $element, $folder);
            if ($packageId !== $canonicalId) {
                throw new \RuntimeException('Canonical Core package does not own child ' . $type . ':' . $element . '.');
            }
        }

        $siteIds = $this->updateSiteIdsForExtension($db, $legacyId);

        $deleteLinks = $db->getQuery(true)
            ->delete($db->quoteName('#__update_sites_extensions'))
            ->where($db->quoteName('extension_id') . ' = :legacyId')
            ->bind(':legacyId', $legacyId, ParameterType::INTEGER);
        $db->setQuery($deleteLinks)->execute();

        $deleteExtension = $db->getQuery(true)
            ->delete($db->quoteName('#__extensions'))
            ->where($db->quoteName('extension_id') . ' = :legacyId')
            ->where($db->quoteName('type') . ' = ' . $db->quote('package'))
            ->where($db->quoteName('element') . ' = ' . $db->quote(self::LEGACY_PACKAGE))
            ->bind(':legacyId', $legacyId, ParameterType::INTEGER);
        $db->setQuery($deleteExtension)->execute();

        foreach ($siteIds as $siteId) {
            $countQuery = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__update_sites_extensions'))
                ->where($db->quoteName('update_site_id') . ' = :siteId')
                ->bind(':siteId', $siteId, ParameterType::INTEGER);
            if ((int) $db->setQuery($countQuery)->loadResult() === 0) {
                $deleteSite = $db->getQuery(true)
                    ->delete($db->quoteName('#__update_sites'))
                    ->where($db->quoteName('update_site_id') . ' = :siteId')
                    ->bind(':siteId', $siteId, ParameterType::INTEGER);
                $db->setQuery($deleteSite)->execute();
            }
        }

        $legacyManifest = JPATH_ADMINISTRATOR . '/manifests/packages/' . self::LEGACY_PACKAGE . '.xml';
        if (is_file($legacyManifest) && !@unlink($legacyManifest)) {
            throw new \RuntimeException('Unable to remove legacy Core package manifest.');
        }
    }

    private function packageExtensionId(DatabaseInterface $db, string $element): int
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('package'))
            ->where($db->quoteName('element') . ' = :packageElement')
            ->bind(':packageElement', $element);

        return (int) $db->setQuery($query, 0, 1)->loadResult();
    }

    private function childPackageId(DatabaseInterface $db, string $type, string $element, ?string $folder): int
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('package_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :childType')
            ->where($db->quoteName('element') . ' = :childElement')
            ->bind(':childType', $type)
            ->bind(':childElement', $element);

        if ($folder !== null) {
            $query->where($db->quoteName('folder') . ' = :childFolder')->bind(':childFolder', $folder);
        }

        return (int) $db->setQuery($query, 0, 1)->loadResult();
    }

    private function updateSiteIdsForExtension(DatabaseInterface $db, int $extensionId): array
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('update_site_id'))
            ->from($db->quoteName('#__update_sites_extensions'))
            ->where($db->quoteName('extension_id') . ' = :extensionId')
            ->bind(':extensionId', $extensionId, ParameterType::INTEGER);

        return array_map('intval', (array) $db->setQuery($query)->loadColumn());
    }
}
