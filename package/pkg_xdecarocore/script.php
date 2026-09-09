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
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class pkg_xdecarocoreInstallerScript
{
    /**
     * Core's system plugin is a required package child. Keep it active after
     * both clean installs and upgrades so the package has one deterministic
     * operational state across Joomla installers and CLI installs.
     *
     * Core 1.5.0 also shipped administrator submenu links with a duplicated
     * index.php? prefix. Normalize any already-persisted menu rows during the
     * 1.5.1 upgrade so existing installations are repaired automatically.
     */
    public function postflight($type, $parent): void
    {
        if ($type === 'uninstall') {
            return;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $this->enableCorePlugin($db);
            $this->repairAdministratorMenuLinks($db);
        } catch (\Throwable $exception) {
            Log::add(
                'Core package post-install normalization failed: ' . $exception->getMessage(),
                Log::WARNING,
                'pkg_xdecarocore'
            );
            Factory::getApplication()->enqueueMessage(
                'Core by xdecaro was installed, but one or more post-install normalizations could not be completed automatically.',
                'warning'
            );
        }
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
