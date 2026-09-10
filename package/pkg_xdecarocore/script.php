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

final class pkg_xdecarocoreInstallerScript extends InstallerScript
{
    /** @var string */
    protected $minimumJoomla = '6.0.0';

    /** @var string */
    protected $minimumPhp = '8.3.0';

    /**
     * Keep the required Core system plugin active and normalize historical
     * installation state. Core 2.x also removes the retired 1.x namespace
     * compatibility library from upgraded installations.
     */
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
            Log::add(
                'Core package database service resolution failed: ' . $exception->getMessage(),
                Log::WARNING,
                'pkg_xdecarocore'
            );
            $this->enqueueNormalizationWarning();

            return;
        }

        try {
            $this->enableCorePlugin($db);
        } catch (\Throwable $exception) {
            Log::add(
                'Core package plugin activation failed: ' . $exception->getMessage(),
                Log::WARNING,
                'pkg_xdecarocore'
            );
            $normalizationFailed = true;
        }

        try {
            $this->removeRetiredCompatibilityLibrary($db);
        } catch (\Throwable $exception) {
            Log::add(
                'Core package retired compatibility library removal failed: ' . $exception->getMessage(),
                Log::WARNING,
                'pkg_xdecarocore'
            );
            $normalizationFailed = true;
        }

        try {
            $this->repairAdministratorMenuLinks($db);
        } catch (\Throwable $exception) {
            Log::add(
                'Core package administrator menu normalization failed: ' . $exception->getMessage(),
                Log::WARNING,
                'pkg_xdecarocore'
            );
            $normalizationFailed = true;
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

    /**
     * Remove the retired 1.x namespace compatibility child on upgrade.
     *
     * The child was part of the 1.5 package, so merely omitting it from the
     * 2.x manifest would leave its extension record and autoload mapping on
     * upgraded sites. Joomla's installer performs the removal so files,
     * manifest data and extension metadata remain consistent.
     */
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
            $normalized = preg_replace(
                '#^(?:index\.php\?)+(?=option=com_xdecarocore(?:&|$))#',
                'index.php?',
                $link
            );

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
}
