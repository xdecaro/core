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
        'core' => ['name' => 'Core', 'package' => 'pkg_xdecarocore', 'component' => 'com_xdecarocore', 'version' => '2.0.1', 'channel' => 'stable'],
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

    /**
     * @return array{updates:array<string,array<string,mixed>>,failed:bool}
     */
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
            return ['updates' => [], 'failed' => true];
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

            $identity = $this->extensionIdentity(
                $candidate['type'],
                $candidate['element'],
                $candidate['folder'],
                $candidate['client_id']
            );

            if (!isset($updates[$identity]) || version_compare($candidate['version'], $updates[$identity]['version'], '>')) {
                $updates[$identity] = $candidate;
            }
        }

        return ['updates' => $updates, 'failed' => false];
    }

    private function buildProduct(string $key, array $definition, array $extensions, array $updates): array
    {
        $package = $definition['package'] !== '' ? $this->findExtension($extensions, 'package', $definition['package']) : null;
        $component = $definition['component'] !== '' ? $this->findExtension($extensions, 'component', $definition['component']) : null;
        $partial = $package === null && $component !== null;
        $installed = $package !== null || $partial;
        $installedVersion = $package !== null ? $package['version'] : ($component !== null ? $component['version'] : '');
        $catalogVersion = (string) $definition['version'];
        $availableVersion = $catalogVersion;

        $packageUpdateIdentity = $this->extensionIdentity('package', (string) $definition['package'], '', 0);
        if ($definition['package'] !== '' && isset($updates[$packageUpdateIdentity])) {
            $updateVersion = (string) $updates[$packageUpdateIdentity]['version'];
            if ($availableVersion === '' || version_compare($updateVersion, $availableVersion, '>')) {
                $availableVersion = $updateVersion;
            }
        }

        $catalogBehind = $installedVersion !== ''
            && $catalogVersion !== ''
            && version_compare($installedVersion, $catalogVersion, '>');

        // The displayed available version must never be older than the version already installed.
        if ($installedVersion !== ''
            && ($availableVersion === '' || version_compare($installedVersion, $availableVersion, '>'))) {
            $availableVersion = $installedVersion;
        }

        $children = [];
        if ($package !== null) {
            $children = $this->resolvePackageChildren($package, $definition, $extensions);
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
            'catalog_version' => $catalogVersion,
            'installed_version' => $installedVersion,
            'available_version' => $availableVersion,
            'catalog_behind' => $catalogBehind,
            'installed' => $installed,
            'partial' => $partial,
            'status' => $status,
            'children' => $children,
            'disabled_count' => $disabledCount,
            'open_url' => $component !== null ? 'index.php?option=' . rawurlencode((string) $definition['component']) : '',
        ];
    }

    /**
     * Resolve the real children declared by the installed package manifest.
     * Joomla package_id is retained only as a fallback because older/manual installs can leave stale relationships.
     */
    private function resolvePackageChildren(array $package, array $definition, array $extensions): array
    {
        $members = $this->loadPackageManifestMembers((string) $package['element']);
        $children = [];
        $seen = [];

        if ($members !== null) {
            foreach ($members as $member) {
                $extension = $this->findPackageMemberExtension($extensions, $member);
                if ($extension === null || isset($seen[$extension['extension_id']])) {
                    continue;
                }

                $children[] = $extension;
                $seen[$extension['extension_id']] = true;
            }
        } else {
            foreach ($extensions as $extension) {
                if ($extension['package_id'] !== $package['extension_id']) {
                    continue;
                }

                $children[] = $extension;
                $seen[$extension['extension_id']] = true;
            }
        }

        // Always retain the catalog component when it exists, even if a legacy package manifest is incomplete.
        if ($definition['component'] !== '') {
            $component = $this->findExtension($extensions, 'component', (string) $definition['component']);
            if ($component !== null && !isset($seen[$component['extension_id']])) {
                $children[] = $component;
            }
        }

        return $children;
    }

    /**
     * @return array<int,array{type:string,id:string,group:string,client:string}>|null
     */
    private function loadPackageManifestMembers(string $packageElement): ?array
    {
        if (!defined('JPATH_MANIFESTS') || $packageElement === '') {
            return null;
        }

        $path = JPATH_MANIFESTS . '/packages/' . basename($packageElement) . '.xml';
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false || trim($contents) === '') {
            return null;
        }

        try {
            $manifest = new \SimpleXMLElement($contents);
        } catch (\Throwable $exception) {
            return null;
        }

        if (!isset($manifest->files)) {
            return [];
        }

        $members = [];
        foreach ($manifest->files->file as $file) {
            $type = trim((string) $file['type']);
            $id = trim((string) $file['id']);
            if ($type === '' || $id === '') {
                continue;
            }

            $members[] = [
                'type' => $type,
                'id' => $id,
                'group' => trim((string) $file['group']),
                'client' => strtolower(trim((string) $file['client'])),
            ];
        }

        return $members;
    }

    private function findPackageMemberExtension(array $extensions, array $member): ?array
    {
        foreach ($extensions as $extension) {
            if ($extension['type'] !== $member['type'] || $extension['element'] !== $member['id']) {
                continue;
            }

            if ($member['type'] === 'plugin' && $member['group'] !== '' && $extension['folder'] !== $member['group']) {
                continue;
            }
            if ($member['type'] === 'module' && $member['client'] !== '') {
                $expectedClient = in_array($member['client'], ['administrator', 'admin'], true) ? 1 : 0;
                if ((int) $extension['client_id'] !== $expectedClient) {
                    continue;
                }
            }

            return $extension;
        }

        return null;
    }

    private function buildDiagnostics(array $products, array $extensions, bool $updateLoadFailed = false): array
    {
        $checks = [];
        $ecosystemCoherent = !$updateLoadFailed;
        $corePackage = $this->findExtension($extensions, 'package', 'pkg_xdecarocore');
        $coreComponent = $this->findExtension($extensions, 'component', 'com_xdecarocore');
        $coreLibrary = $this->findExtension($extensions, 'library', 'xdecaro/core');
        $corePlugin = $this->findExtension($extensions, 'plugin', 'xdecarocore', 'system');

        $checks[] = $this->diagnostic(
            $corePackage !== null ? 'success' : 'danger',
            'COM_XDECAROCORE_DIAGNOSTIC_CORE_PACKAGE',
            $corePackage !== null ? 'COM_XDECAROCORE_DIAGNOSTIC_REGISTERED' : 'COM_XDECAROCORE_DIAGNOSTIC_NOT_FOUND'
        );
        $checks[] = $this->diagnostic(
            $coreComponent !== null ? 'success' : 'danger',
            'COM_XDECAROCORE_DIAGNOSTIC_CORE_DASHBOARD',
            $coreComponent !== null ? 'COM_XDECAROCORE_DIAGNOSTIC_COMPONENT_AVAILABLE' : 'COM_XDECAROCORE_DIAGNOSTIC_COMPONENT_NOT_FOUND'
        );
        $checks[] = $this->diagnostic(
            $coreLibrary !== null ? 'success' : 'danger',
            'COM_XDECAROCORE_DIAGNOSTIC_CORE_LIBRARY',
            $coreLibrary === null
                ? 'COM_XDECAROCORE_DIAGNOSTIC_NOT_FOUND_FEMININE'
                : ($coreLibrary['version'] !== ''
                    ? 'COM_XDECAROCORE_DIAGNOSTIC_VERSION'
                    : 'COM_XDECAROCORE_DIAGNOSTIC_VERSION_UNDECLARED'),
            $coreLibrary !== null && $coreLibrary['version'] !== '' ? [(string) $coreLibrary['version']] : []
        );
        $checks[] = $this->diagnostic(
            $corePlugin !== null && (int) $corePlugin['enabled'] === 1 ? 'success' : 'danger',
            'COM_XDECAROCORE_DIAGNOSTIC_CORE_PLUGIN',
            $corePlugin === null
                ? 'COM_XDECAROCORE_DIAGNOSTIC_NOT_FOUND'
                : ((int) $corePlugin['enabled'] === 1
                    ? 'COM_XDECAROCORE_DIAGNOSTIC_ENABLED'
                    : 'COM_XDECAROCORE_DIAGNOSTIC_DISABLED')
        );

        if ($updateLoadFailed) {
            $checks[] = $this->diagnostic(
                'warning',
                'COM_XDECAROCORE_DIAGNOSTIC_UPDATE_CACHE',
                'COM_XDECAROCORE_DIAGNOSTIC_UPDATE_CACHE_UNAVAILABLE'
            );
        }

        foreach ($products as $product) {
            if ($product['partial']) {
                $ecosystemCoherent = false;
                $checks[] = $this->diagnostic(
                    'danger',
                    '',
                    'COM_XDECAROCORE_DIAGNOSTIC_PARTIAL_INSTALLATION',
                    [],
                    (string) $product['name']
                );
            } elseif ($product['disabled_count'] > 0) {
                $ecosystemCoherent = false;
                $checks[] = $this->diagnostic(
                    'warning',
                    '',
                    'COM_XDECAROCORE_DIAGNOSTIC_DISABLED_CHILDREN',
                    [(int) $product['disabled_count']],
                    (string) $product['name']
                );
            }

            if ($product['catalog_behind']) {
                $checks[] = $this->diagnostic(
                    'info',
                    '',
                    'COM_XDECAROCORE_DIAGNOSTIC_CATALOG_BEHIND',
                    [(string) $product['catalog_version'], (string) $product['installed_version']],
                    (string) $product['name']
                );
            }
        }

        if ($ecosystemCoherent) {
            $checks[] = $this->diagnostic(
                'success',
                'COM_XDECAROCORE_DIAGNOSTIC_ECOSYSTEM',
                'COM_XDECAROCORE_DIAGNOSTIC_ECOSYSTEM_COHERENT'
            );
        }

        return $checks;
    }

    /**
     * Build a translated diagnostic while retaining its language keys for alternate renderers.
     */
    private function diagnostic(
        string $level,
        string $labelKey,
        string $detailKey,
        array $detailArgs = [],
        string $label = ''
    ): array {
        return [
            'level' => $level,
            'label_key' => $labelKey,
            'label_args' => [],
            'detail_key' => $detailKey,
            'detail_args' => $detailArgs,
            'label' => $labelKey !== '' ? Text::_($labelKey) : $label,
            'detail' => $detailArgs !== [] ? Text::sprintf($detailKey, ...$detailArgs) : Text::_($detailKey),
        ];
    }

    private function extensionIdentity(string $type, string $element, string $folder = '', int $clientId = 0): string
    {
        return strtolower(trim($type)) . '|' . strtolower(trim($element)) . '|' . strtolower(trim($folder)) . '|' . $clientId;
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

    /**
     * Build exact identities from catalog roots and the contents of installed known packages.
     * package_id is only a fallback for legacy/manual installs with an unreadable package manifest.
     *
     * @return array<string,bool>
     */
    private function buildSuiteExtensionIdentities(array $extensions): array
    {
        $identities = [];
        $packageIds = [];

        foreach (self::CATALOG as $definition) {
            foreach (['package' => 'package', 'component' => 'component'] as $field => $type) {
                $element = (string) $definition[$field];
                if ($element === '') {
                    continue;
                }

                foreach ($extensions as $extension) {
                    if ($extension['type'] !== $type || $extension['element'] !== $element) {
                        continue;
                    }

                    $identities[$this->extensionIdentity(
                        (string) $extension['type'],
                        (string) $extension['element'],
                        (string) $extension['folder'],
                        (int) $extension['client_id']
                    )] = true;

                    if ($type === 'package') {
                        $packageIds[(int) $extension['extension_id']] = true;
                    }
                }
            }
        }

        foreach ($extensions as $extension) {
            if ((int) $extension['package_id'] > 0 && isset($packageIds[(int) $extension['package_id']])) {
                $identities[$this->extensionIdentity(
                    (string) $extension['type'],
                    (string) $extension['element'],
                    (string) $extension['folder'],
                    (int) $extension['client_id']
                )] = true;
            }
        }

        foreach (self::CATALOG as $definition) {
            $packageElement = (string) $definition['package'];
            if ($packageElement === '') {
                continue;
            }

            $package = $this->findExtension($extensions, 'package', $packageElement);
            if ($package === null) {
                continue;
            }

            $members = $this->loadPackageManifestMembers($packageElement);
            foreach ($members ?? [] as $member) {
                $extension = $this->findPackageMemberExtension($extensions, $member);
                if ($extension === null) {
                    continue;
                }

                $identities[$this->extensionIdentity(
                    (string) $extension['type'],
                    (string) $extension['element'],
                    (string) $extension['folder'],
                    (int) $extension['client_id']
                )] = true;
            }
        }

        return $identities;
    }

    private function isSuiteExtension(array $extension, array $knownIdentities = []): bool
    {
        $identity = $this->extensionIdentity(
            (string) $extension['type'],
            (string) $extension['element'],
            (string) $extension['folder'],
            (int) $extension['client_id']
        );
        if (isset($knownIdentities[$identity])) {
            return true;
        }

        $element = strtolower(trim((string) $extension['element']));
        if (preg_match('/^(?:pkg|com|mod)_xdecaro[a-z0-9_]*$/', $element) === 1
            || preg_match('/^xdecaro[a-z0-9_\/-]*$/', $element) === 1) {
            return true;
        }

        foreach (self::CATALOG as $definition) {
            $package = strtolower((string) $definition['package']);
            if (strpos($package, 'pkg_decaro') !== 0) {
                continue;
            }

            $legacyElement = substr($package, 4);
            if ($element === $legacyElement
                || in_array($element, ['mod_' . $legacyElement, 'plg_' . $legacyElement, 'lib_' . $legacyElement], true)) {
                return true;
            }
        }

        return false;
    }
}
