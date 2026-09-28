<?php
/**
 * @package     xdecaro.Core
 * @subpackage  com_xdecarocore
 */

namespace xdecaro\Component\Core\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

final class EcosystemService
{
    /**
     * Known xdecaro products. Installed state is always read from Joomla.
     * The catalog version is the newest version known when this Core release was built.
     */
    private const CATALOG = [
        'core' => ['name' => 'Core', 'package' => 'pkg_core', 'component' => 'com_xdecarocore', 'version' => '2.2.6', 'channel' => 'stable'],
        'people' => ['name' => 'People', 'package' => 'pkg_xdecaropeople', 'component' => 'com_xdecaropeople', 'version' => '1.8.3', 'channel' => 'stable'],
        'organizations' => ['name' => 'Organizations', 'package' => 'pkg_xdecaroorganizations', 'component' => 'com_xdecaroorganizations', 'version' => '1.2.23', 'channel' => 'stable'],
        'notifications' => ['name' => 'Notifications', 'package' => 'pkg_xdecaronotifications', 'component' => 'com_xdecaronotifications', 'version' => '1.1.8', 'channel' => 'stable'],
        'tasks' => ['name' => 'Tasks', 'package' => 'pkg_xdecarotasks', 'component' => 'com_xdecarotasks', 'version' => '1.0.0', 'channel' => 'stable'],
        'analytics' => ['name' => 'Analytics', 'package' => 'pkg_xdecaroanalytics', 'component' => 'com_xdecaroanalytics', 'version' => '1.0.0', 'channel' => 'stable'],
        'documents' => ['name' => 'Documents', 'package' => 'pkg_decarodocuments', 'component' => 'com_decarodocuments', 'version' => '1.4.0', 'channel' => 'stable'],
        'forms' => ['name' => 'Forms', 'package' => 'pkg_decaroforms', 'component' => 'com_decaroforms', 'version' => '1.7.0', 'channel' => 'stable'],
        'membership' => ['name' => 'Membership', 'package' => 'pkg_decaromembership', 'component' => 'com_decaromembership', 'version' => '1.9.30', 'channel' => 'stable'],
        'courses' => ['name' => 'Courses', 'package' => 'pkg_decarocourses', 'component' => 'com_decarocourses', 'version' => '1.5.0', 'channel' => 'stable'],
        'events' => ['name' => 'Events', 'package' => 'pkg_decaroevents', 'component' => 'com_decaroevents', 'version' => '1.2.0', 'channel' => 'stable'],
        'competitions' => ['name' => 'Competitions', 'package' => 'pkg_xdecarocompetitions', 'component' => 'com_xdecarocompetitions', 'version' => '1.5.54', 'channel' => 'stable'],
        'finance' => ['name' => 'Finance', 'package' => 'pkg_decarofinance', 'component' => 'com_decarofinance', 'version' => '1.6.0', 'channel' => 'stable'],
        'protocol' => ['name' => 'Protocol', 'package' => 'pkg_decaroprotocol', 'component' => 'com_decaroprotocol', 'version' => '1.5.0', 'channel' => 'stable'],
        'editor' => ['name' => 'Editor', 'package' => 'pkg_decaroeditor', 'component' => '', 'version' => '0.1.0-alpha6', 'channel' => 'prerelease'],
        'draw' => ['name' => 'Draw', 'package' => 'pkg_xdecarodraw', 'component' => 'com_xdecarodraw', 'version' => '1.0.0', 'channel' => 'development'],
        'resources' => ['name' => 'Resources', 'package' => 'pkg_xdecaroresources', 'component' => 'com_xdecaroresources', 'version' => '0.2.0', 'channel' => 'development'],
        'inventory' => ['name' => 'Inventory', 'package' => 'pkg_xdecaroinventory', 'component' => 'com_xdecaroinventory', 'version' => '0.2.0', 'channel' => 'development'],
        'communications' => ['name' => 'Communications', 'package' => '', 'component' => '', 'version' => '', 'channel' => 'planned'],
        'feedback' => ['name' => 'Feedback', 'package' => 'pkg_xdecarofeedback', 'component' => 'com_xdecarofeedback', 'version' => '', 'channel' => 'planned'],
    ];

    /** @var DatabaseInterface */
    private $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function snapshot(): array
    {
        $extensions = $this->loadExtensions();
        $updateState = $this->loadUpdates();
        $availableUpdates = $updateState['updates'];
        $products = [];

        foreach (self::CATALOG as $key => $definition) {
            $products[] = $this->buildProduct($key, $definition, $extensions, $availableUpdates);
        }

        $suiteExtensions = [];
        $suiteIdentities = $this->buildSuiteExtensionIdentities($extensions);
        foreach ($extensions as $extension) {
            if ($this->isSuiteExtension($extension, $suiteIdentities)) {
                $suiteExtensions[] = $extension;
            }
        }

        usort($suiteExtensions, static function (array $a, array $b): int {
            $type = strcmp((string) $a['type'], (string) $b['type']);
            return $type !== 0 ? $type : strcmp((string) $a['name'], (string) $b['name']);
        });

        $summary = [
            'known' => count($products),
            'installed' => 0,
            'not_installed' => 0,
            'updates' => 0,
            'warnings' => 0,
            'technical_extensions' => count($suiteExtensions),
        ];

        foreach ($products as $product) {
            if ($product['installed']) {
                $summary['installed']++;
            } elseif (in_array($product['channel'], ['stable', 'prerelease'], true)) {
                $summary['not_installed']++;
            }

            if ($product['status'] === 'update') {
                $summary['updates']++;
            }

            if ($product['partial'] || $product['disabled_count'] > 0) {
                $summary['warnings']++;
            }
        }

        if ($updateState['failed']) {
            $summary['warnings']++;
        }

        return [
            'products' => $products,
            'extensions' => $suiteExtensions,
            'updates' => $availableUpdates,
            'summary' => $summary,
            'diagnostics' => $this->buildDiagnostics($products, $extensions, $updateState['failed']),
        ];
    }

    private function loadExtensions(): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('extension_id'),
                $this->db->quoteName('package_id'),
                $this->db->quoteName('name'),
                $this->db->quoteName('type'),
                $this->db->quoteName('element'),
                $this->db->quoteName('folder'),
                $this->db->quoteName('client_id'),
                $this->db->quoteName('enabled'),
                $this->db->quoteName('manifest_cache'),
            ])
            ->from($this->db->quoteName('#__extensions'));

        $rows = $this->db->setQuery($query)->loadAssocList();

        foreach ($rows as &$row) {
            $manifest = json_decode((string) ($row['manifest_cache'] ?? ''), true);
            $row['version'] = is_array($manifest) ? (string) ($manifest['version'] ?? '') : '';
        }
        unset($row);

        return $rows;
    }

    private function loadUpdates(): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('u.extension_id'),
                $this->db->quoteName('u.name'),
                $this->db->quoteName('u.element'),
                $this->db->quoteName('u.type'),
                $this->db->quoteName('u.folder'),
                $this->db->quoteName('u.client_id'),
                $this->db->quoteName('u.version'),
                $this->db->quoteName('u.detailsurl'),
            ])
            ->from($this->db->quoteName('#__updates', 'u'));

        try {
            return ['updates' => $this->db->setQuery($query)->loadAssocList(), 'failed' => false];
        } catch (\Throwable $e) {
            return ['updates' => [], 'failed' => true];
        }
    }

    private function buildProduct(string $key, array $definition, array $extensions, array $updates): array
    {
        $matches = array_values(array_filter($extensions, function (array $extension) use ($definition): bool {
            if ($definition['package'] !== '' && $extension['type'] === 'package' && $extension['element'] === $definition['package']) {
                return true;
            }

            return $definition['component'] !== '' && $extension['type'] === 'component' && $extension['element'] === $definition['component'];
        }));

        $installed = $matches !== [];
        $installedVersion = '';
        $disabledCount = 0;
        foreach ($matches as $match) {
            if ($installedVersion === '' && $match['version'] !== '') {
                $installedVersion = $match['version'];
            }
            if ((int) $match['enabled'] !== 1) {
                $disabledCount++;
            }
        }

        $availableVersion = '';
        foreach ($updates as $update) {
            if (($definition['package'] !== '' && $update['element'] === $definition['package'])
                || ($definition['component'] !== '' && $update['element'] === $definition['component'])) {
                if ($availableVersion === '' || version_compare((string) $update['version'], $availableVersion, '>')) {
                    $availableVersion = (string) $update['version'];
                }
            }
        }

        $status = 'not_installed';
        if ($installed) {
            $status = 'current';
            if ($availableVersion !== '' && ($installedVersion === '' || version_compare($availableVersion, $installedVersion, '>'))) {
                $status = 'update';
            }
        }

        return [
            'key' => $key,
            'name' => $definition['name'],
            'package' => $definition['package'],
            'component' => $definition['component'],
            'expected_version' => $definition['version'],
            'channel' => $definition['channel'],
            'installed' => $installed,
            'installed_version' => $installedVersion,
            'available_version' => $availableVersion,
            'status' => $status,
            'partial' => false,
            'disabled_count' => $disabledCount,
        ];
    }

    private function buildSuiteExtensionIdentities(array $extensions): array
    {
        $identities = [];

        foreach ($extensions as $extension) {
            if ((string) ($extension['type'] ?? '') !== 'package') {
                continue;
            }

            $element = (string) ($extension['element'] ?? '');
            if (!$this->isSuitePackageElement($element)) {
                continue;
            }

            $packageId = (int) ($extension['extension_id'] ?? 0);
            if ($packageId > 0) {
                $identities['package_ids'][$packageId] = true;
            }
        }

        return $identities;
    }

    private function isSuiteExtension(array $extension, array $identities): bool
    {
        $type = (string) ($extension['type'] ?? '');
        $element = (string) ($extension['element'] ?? '');
        $packageId = (int) ($extension['package_id'] ?? 0);

        if ($type === 'package' && $this->isSuitePackageElement($element)) {
            return true;
        }

        if ($packageId > 0 && isset($identities['package_ids'][$packageId])) {
            return true;
        }

        return $this->isSuiteElement($element);
    }

    private function isSuitePackageElement(string $element): bool
    {
        return str_starts_with($element, 'pkg_xdecaro') || str_starts_with($element, 'pkg_decaro') || $element === 'pkg_core';
    }

    private function isSuiteElement(string $element): bool
    {
        foreach (['com_xdecaro', 'plg_xdecaro', 'mod_xdecaro', 'lib_xdecaro', 'com_decaro', 'plg_decaro', 'mod_decaro', 'lib_decaro'] as $prefix) {
            if (str_starts_with($element, $prefix)) {
                return true;
            }
        }

        return in_array($element, ['xdecarocore'], true);
    }

    private function buildDiagnostics(array $products, array $extensions, bool $updateFailed): array
    {
        $diagnostics = [];

        $diagnostics[] = [
            'label' => Text::_('COM_XDECAROCORE_DIAGNOSTIC_JOOMLA'),
            'status' => version_compare(JVERSION, '6.1.3', '>=') ? 'ok' : 'warning',
            'value' => JVERSION,
        ];
        $diagnostics[] = [
            'label' => Text::_('COM_XDECAROCORE_DIAGNOSTIC_PHP'),
            'status' => version_compare(PHP_VERSION, '8.3.0', '>=') ? 'ok' : 'warning',
            'value' => PHP_VERSION,
        ];
        $diagnostics[] = [
            'label' => Text::_('COM_XDECAROCORE_DIAGNOSTIC_UPDATE_SOURCE'),
            'status' => $updateFailed ? 'warning' : 'ok',
            'value' => $updateFailed ? Text::_('COM_XDECAROCORE_DIAGNOSTIC_UPDATE_SOURCE_FAILED') : Text::_('COM_XDECAROCORE_DIAGNOSTIC_UPDATE_SOURCE_OK'),
        ];

        foreach ($products as $product) {
            if ($product['partial'] || $product['disabled_count'] > 0) {
                $diagnostics[] = [
                    'label' => $product['name'],
                    'status' => 'warning',
                    'value' => Text::_('COM_XDECAROCORE_STATUS_PARTIAL'),
                ];
            }
        }

        return $diagnostics;
    }
}
