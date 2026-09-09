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
use Joomla\Database\DatabaseInterface;

final class PkgXdecaroCoreInstallerScript
{
    /**
     * The Core system plugin is a required package child and must be active
     * after both a clean install and an upgrade.
     */
    public function postflight($type, $parent): void
    {
        if ($type === 'uninstall') {
            return;
        }

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('xdecarocore'));

            $db->setQuery($query)->execute();
        } catch (\Throwable $exception) {
            Factory::getApplication()->enqueueMessage(
                'Core by xdecaro was installed, but its required system plugin could not be enabled automatically.',
                'warning'
            );
        }
    }
}
