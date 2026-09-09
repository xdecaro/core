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
     */
    public function postflight($type, $parent): void
    {
        if ($type === 'uninstall') {
            return;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
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
        } catch (\Throwable $exception) {
            Log::add(
                'Core system plugin could not be enabled automatically: ' . $exception->getMessage(),
                Log::WARNING,
                'pkg_xdecarocore'
            );
            Factory::getApplication()->enqueueMessage(
                'Core by xdecaro was installed, but its required system plugin could not be enabled automatically.',
                'warning'
            );
        }
    }
}
