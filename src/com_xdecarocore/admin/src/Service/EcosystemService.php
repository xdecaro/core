<?php
/**
 * @package     xdecaro.Core
 * @subpackage  com_xdecarocore
 */

namespace xdecaro\Component\Core\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

final class EcosystemService
{
    /**
     * Known xdecaro products. Installed state is always read from Joomla.
     * The catalog version is the newest version known when this Core release was built.
     */
    private const CATALOG = [
        'core' => ['name' => 'Core', 'package' => 'pkg_xdecarocore', 'component' => 'com_xdecarocore', 'version' => '1.5.0', 'channel' => 'stable'],
        'people' => ['name' => 'People', 'package' => 'pkg_xdecaropeople', 'component' => 'com_xdecaropeople', 'version' => '1.0.1', 'channel' => 'stable'],
        'organizations' => ['name' => 'Organizations', 'package' => 'pkg_xdecaroorganizations', 'component' => 'com_xdecaroorganizations', 'version' => '1.0.0', 'channel' => 'stable'],
        'notifications' => ['name' => 'Notifications', 'package' => 'pkg_xdecaronotifications', 'component' => 'com_xdecaronotifications', 'version' => '1.0.2', 'channel' => 'stable'],
        'tasks' => ['name' => 'Tasks', 'package' => 'pkg_xdecarotasks', 'component' => 'com_xdecarotasks', 'version' => '1.0.0', 'channel' => 'stable'],
        'analytics' => ['name' => 'Analytics', 'package' => 'pkg_xdecaroanalytics', 'component' => 'com_xdecaroanalytics', 'version' => '1.0.0', 'channel' => 'stable'],
        'documents' => ['name' => 'Documents', 'package' => 'pkg_decarodocuments', 'component' => 'com_decarodocuments', 'version' => '1.3.0', 'channel' => 'stable'],
        'forms' => ['name' => 'Forms', 'package' => 'pkg_decaroforms', 'component' => 'com_decaroforms', 'version' => '1.7.0', 'channel' => 'stable'],
        'membership' => ['name' => 'Membership', 'package' => 'pkg_decaromembership', 'component' => 'com_decaromembership', 'version' => '1.4.0', 'channel' => 'stable'],
        'courses' => ['name' => 'Courses', 'package' => 'pkg_decarocourses', 'component' => 'com_decarocourses', 'version' => '1.5.0', 'channel' => 'stable'],
        'events' => ['name' => 'Events', 'package' => 'pkg_decaroevents', 'component' => 'com_decaroevents', 'version' => '1.2.0', 'channel' => 'stable'],
        'competitions' => ['name' => 'Competitions', 'package' => 'pkg_xdecarocompetitions', 'component' => 'com_xdecarocompetitions', 'version' => '1.3.0', 'channel' => 'stable'],
        'finance' => ['name' => 'Finance', 'package' => 'pkg_decarofinance', 'component' => 'com_decarofinance', 'version' => '1.3.0', 'channel' => 'stable'],
        'protocol' => ['name' => 'Protocol', 'package' => 'pkg_decaroprotocol', 'component' => 'com_decaroprotocol', 'version' => '1.4.0', 'channel' => 'stable'],
        'editor' => ['name' => 'Editor', 'package' => 'pkg_decaroeditor', 'component' => '', 'version' => '0.1.0-alpha6', 'channel' => 'prerelease'],
        'draw' => ['name' => 'Draw', 'package' => 'pkg_xdecarodraw', 'component' => 'com_xdecarodraw', 'version' => '1.0.0', 'channel' => 'development'],
        'resources' => ['name' => 'Resources', 'package' => 'pkg_xdecaroresources', 'component' => 'com_xdecaroresources', 'version' => '0.2.0', 'channel' => 'development'],
        'inventory' => ['name' => 'Inventory', 'package' => 'pkg_xdecaroinventory', 'component' => 'com_xdecaroinventory', 'version' => '0.2.0', 'channel' => 'development'],
        'communications' => ['name' => 'Communications', 'package' => '', 'component' => '', 'version' => '', 'channel' => 'planned'],
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
        $availableUpdates = $this->loadUpdates();
        $products = [];

        foreach (self::CATALOG as $key => $definition) {
            $products[] = $this->buildProduct($key, $definition, $extensions, $availableUpdates);
        }

        $suiteExtensions = [];
        foreach ($extensions as $extension) {
            if ($this->isXdecaroExtension($extension)) {
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

        return [
            'products' => $products,
            'extensions' => $suiteExtensions,
            'updates' => $availableUpdates,
            'summary' => $summary,
            'diagnostics' => $this->buildDiagnostics($products, $extensions),
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

        $rows = $this->db->setQuery($query)->loadObjectList();
        $result = [];

        foreach ($rows ?: [] as $row) {
            $manifest = json_decode((string) $row->manifest_cache, true);
            if (!is_array($manifest)) {
                $manifest = [];
            }

            $result[] = [
                'extension_id' => (int) $row->extension_id,
                'package_id' => (int) $row->package_id,
                'name' => (string) $row->name,
                'type' => (string) $row->type,
                'element' => (string) $row->element,
                'folder' => (string) $row->folder,
                'client_id' => (int) $row->client_id,
                'enabled' => (int) $row->enabled,
                'version' => isset($manifest['version']) ? trim((string) $manifest['version']) : '',
                'author' => isset($manifest['author']) ? trim((string) $manifest['author']) : '',
            ];
        }

        return $result;
    }

    private function loadUpdates(): array
    {
        try {
            $query = $this->db->getQuery(true)
                ->select([
                    $this->db->quoteName('name'),
                    $this->db->quoteName('element'),
                    $this->db->quoteName('type'),
                    $this->db->quoteName('folder'),
                    $this->db->quoteName('client_id'),
                    $this->db->quoteName('version'),
                ])
                ->from($this->db->quoteName('#__updates'));

            $rows = $this->db->setQuery($query)->loadObjectList();
        } catch (\Throwable $exception) {
            return [];
        }

        $updates = [];
        foreach ($rows ?: [] as $row) {
            $element = (string) $row->element;
            if ($element === '') {
                continue;
            }

            $candidate = [
                'name' => (string) $row->name,
                'element' => $element,
                'type' => (string) $row->type,
                'folder' => (string) $row->folder,
                'client_id' => (int) $row->client_id,
                'version' => trim((string) $row->version),
            ];

            if (!isset($updates[$element]) || version_compare($candidate['version'], $updates[$element]['version'], '>')) {
                $updates[$element] = $candidate;
            }
        }

        return $updates;
    }

    private function buildProduct(string $key, array $definition, array $extensions, array $updates): array
    {
        $package = $definition['package'] !== '' ? $this->findExtension($extensions, 'package', $definition['package']) : null;
        $component = $definition['component'] !== '' ? $this->findExtension($extensions, 'component', $definition['component']) : null;
        $partial = $package === null && $component !== null;
        $installed = $package !== null || $partial;
        $installedVersion = $package !== null ? $package['version'] : ($component !== null ? $component['version'] : '');
        $availableVersion = (string) $definition['version'];

        if ($definition['package'] !== '' && isset($updates[$definition['package']])) {
            $updateVersion = (string) $updates[$definition['package']]['version'];
            if ($availableVersion === '' || version_compare($updateVersion, $availableVersion, '>')) {
                $availableVersion = $updateVersion;
            }
        }

        $children = [];
        if ($package !== null) {
            foreach ($extensions as $extension) {
                if ($extension['package_id'] === $package['extension_id']) {
                    $children[] = $extension;
                }
            }
        } elseif ($component !== null) {
            $children[] = $component;
        }

        usort($children, static function (array $a, array $b): int {
            $type = strcmp((string) $a['type'], (string) $b['type']);
            return $type !== 0 ? $type : strcmp((string) $a['name'], (string) $b['name']);
        });

        $disabledCount = 0;
        foreach ($children as $child) {
            if ((int) $child['enabled'] === 0 && in_array($child['type'], ['plugin', 'module'], true)) {
                $disabledCount++;
            }
        }

        $status = 'not_installed';
        if ($definition['channel'] === 'planned' && !$installed) {
            $status = 'planned';
        } elseif ($definition['channel'] === 'development' && !$installed) {
            $status = 'development';
        } elseif ($partial) {
            $status = 'partial';
        } elseif ($installed) {
            if ($installedVersion !== '' && $availableVersion !== '' && version_compare($installedVersion, $availableVersion, '<')) {
                $status = 'update';
            } elseif ($installedVersion !== '' && $availableVersion !== '' && version_compare($installedVersion, $availableVersion, '>')) {
                $status = 'ahead';
            } else {
                $status = 'current';
            }
        }

        return [
            'key' => $key,
            'name' => (string) $definition['name'],
            'package' => (string) $definition['package'],
            'component' => (string) $definition['component'],
            'channel' => (string) $definition['channel'],
            'catalog_version' => (string) $definition['version'],
            'installed_version' => $installedVersion,
            'available_version' => $availableVersion,
            'installed' => $installed,
            'partial' => $partial,
            'status' => $status,
            'children' => $children,
            'disabled_count' => $disabledCount,
            'open_url' => $component !== null ? 'index.php?option=' . rawurlencode((string) $definition['component']) : '',
        ];
    }

    private function buildDiagnostics(array $products, array $extensions): array
    {
        $checks = [];
        $corePackage = $this->findExtension($extensions, 'package', 'pkg_xdecarocore');
        $coreComponent = $this->findExtension($extensions, 'component', 'com_xdecarocore');
        $coreLibrary = $this->findExtension($extensions, 'library', 'xdecaro/core');
        $corePlugin = $this->findExtension($extensions, 'plugin', 'xdecarocore', 'system');

        $checks[] = [
            'level' => $corePackage !== null ? 'success' : 'danger',
            'label' => 'Package Core',
            'detail' => $corePackage !== null ? 'Registrato in Joomla' : 'Non trovato',
        ];
        $checks[] = [
            'level' => $coreComponent !== null ? 'success' : 'danger',
            'label' => 'Dashboard xdecaro',
            'detail' => $coreComponent !== null ? 'Componente amministrativo disponibile' : 'Componente amministrativo non trovato',
        ];
        $checks[] = [
            'level' => $coreLibrary !== null ? 'success' : 'danger',
            'label' => 'Libreria Core',
            'detail' => $coreLibrary !== null ? 'Versione ' . ($coreLibrary['version'] ?: 'non dichiarata') : 'Non trovata',
        ];
        $checks[] = [
            'level' => $corePlugin !== null && (int) $corePlugin['enabled'] === 1 ? 'success' : 'danger',
            'label' => 'Plugin di sistema Core',
            'detail' => $corePlugin === null ? 'Non trovato' : ((int) $corePlugin['enabled'] === 1 ? 'Abilitato' : 'Disabilitato'),
        ];

        foreach ($products as $product) {
            if ($product['partial']) {
                $checks[] = [
                    'level' => 'danger',
                    'label' => $product['name'],
                    'detail' => 'Installazione parziale: componente presente senza package principale',
                ];
            } elseif ($product['disabled_count'] > 0) {
                $checks[] = [
                    'level' => 'warning',
                    'label' => $product['name'],
                    'detail' => $product['disabled_count'] . ' plugin/moduli del package risultano disabilitati',
                ];
            }
        }

        if (count($checks) === 4) {
            $checks[] = [
                'level' => 'success',
                'label' => 'Ecosistema',
                'detail' => 'Nessuna installazione parziale o estensione tecnica disabilitata rilevata',
            ];
        }

        return $checks;
    }

    private function findExtension(array $extensions, string $type, string $element, string $folder = ''): ?array
    {
        foreach ($extensions as $extension) {
            if ($extension['type'] !== $type || $extension['element'] !== $element) {
                continue;
            }
            if ($folder !== '' && $extension['folder'] !== $folder) {
                continue;
            }
            return $extension;
        }

        return null;
    }

    private function isXdecaroExtension(array $extension): bool
    {
        $haystack = strtolower(
            (string) $extension['name'] . ' ' .
            (string) $extension['element'] . ' ' .
            (string) $extension['author']
        );

        return strpos($haystack, 'xdecaro') !== false
            || strpos($haystack, 'decaro') !== false
            || strpos($haystack, 'luca de caro') !== false;
    }
}
