<?php
/**
 * Dependency-free smoke test for the Core ecosystem dashboard catalog, UI assets and menu contract.
 */

namespace Joomla\Database {
    interface DatabaseInterface {}
}

namespace {
    define('_JEXEC', 1);

    $manifestRoot = sys_get_temp_dir() . '/xdecaro-core-dashboard-' . getmypid();
    @mkdir($manifestRoot . '/packages', 0777, true);
    define('JPATH_MANIFESTS', $manifestRoot);

    require_once __DIR__ . '/../src/com_xdecarocore/admin/src/Service/EcosystemService.php';

    $reflection = new \ReflectionClass(\xdecaro\Component\Core\Administrator\Service\EcosystemService::class);
    $constant = $reflection->getReflectionConstant('CATALOG');
    if ($constant === false) {
        throw new \RuntimeException('Dashboard catalog constant is missing.');
    }

    $catalog = $constant->getValue();
    if (!is_array($catalog) || count($catalog) < 15) {
        throw new \RuntimeException('Dashboard catalog is unexpectedly small.');
    }

    $required = [
        'core' => ['pkg_xdecarocore', '1.5.6'],
        'forms' => ['pkg_decaroforms', '1.7.0'],
        'courses' => ['pkg_decarocourses', '1.5.0'],
        'competitions' => ['pkg_xdecarocompetitions', '1.3.0'],
        'documents' => ['pkg_decarodocuments', '1.3.0'],
        'membership' => ['pkg_decaromembership', '1.4.0'],
        'finance' => ['pkg_decarofinance', '1.3.0'],
        'protocol' => ['pkg_decaroprotocol', '1.4.0'],
    ];

    foreach ($required as $key => $expected) {
        if (!isset($catalog[$key])) {
            throw new \RuntimeException('Missing dashboard product: ' . $key);
        }
        if (($catalog[$key]['package'] ?? '') !== $expected[0] || ($catalog[$key]['version'] ?? '') !== $expected[1]) {
            throw new \RuntimeException('Dashboard catalog mismatch for ' . $key);
        }
    }

    if (($catalog['editor']['channel'] ?? '') !== 'prerelease') {
        throw new \RuntimeException('Editor must remain marked as prerelease.');
    }
    if (($catalog['communications']['channel'] ?? '') !== 'planned') {
        throw new \RuntimeException('Communications must remain marked as planned.');
    }

    $manifest = simplexml_load_file(__DIR__ . '/../src/com_xdecarocore/xdecarocore.xml');
    if ($manifest === false) {
        throw new \RuntimeException('Core administrator component manifest is invalid.');
    }

    $expectedLinks = [
        'option=com_xdecarocore&view=dashboard&layout=products',
        'option=com_xdecarocore&view=dashboard&layout=extensions',
        'option=com_xdecarocore&view=dashboard&layout=updates',
        'option=com_xdecarocore&view=dashboard&layout=diagnostics',
        'option=com_xdecarocore&view=dashboard&layout=information',
    ];
    $actualLinks = [];

    foreach ($manifest->administration->submenu->menu as $menu) {
        $link = trim((string) $menu['link']);
        if ($link !== '') {
            $actualLinks[] = $link;
        }
    }

    if ($actualLinks !== $expectedLinks) {
        throw new \RuntimeException('Dashboard submenu links are not normalized for Joomla administrator routing.');
    }

    foreach ($actualLinks as $link) {
        if (strpos($link, 'index.php?') === 0) {
            throw new \RuntimeException('Dashboard submenu link must not contain an index.php? prefix in the manifest.');
        }
    }

    // Reproduce a stale package_id relationship: Courses children incorrectly point at the Forms package.
    // Installed package manifests must win over that stale database relationship.
    file_put_contents(
        $manifestRoot . '/packages/pkg_decaroforms.xml',
        '<?xml version="1.0"?><extension type="package"><files>'
        . '<file type="component" id="com_decaroforms">com.zip</file>'
        . '<file type="plugin" id="decaroforms" group="system">system.zip</file>'
        . '</files></extension>'
    );
    file_put_contents(
        $manifestRoot . '/packages/pkg_decarocourses.xml',
        '<?xml version="1.0"?><extension type="package"><files>'
        . '<file type="component" id="com_decarocourses">com.zip</file>'
        . '<file type="plugin" id="decarocourses" group="xdecaroanalytics">analytics.zip</file>'
        . '<file type="plugin" id="decarocourses" group="task">task.zip</file>'
        . '</files></extension>'
    );

    $extensions = [
        ['extension_id' => 10077, 'package_id' => 0, 'name' => 'Forms', 'type' => 'package', 'element' => 'pkg_decaroforms', 'folder' => '', 'client_id' => 0, 'enabled' => 1, 'version' => '1.7.0', 'author' => 'Luca De Caro'],
        ['extension_id' => 10078, 'package_id' => 0, 'name' => 'Courses', 'type' => 'package', 'element' => 'pkg_decarocourses', 'folder' => '', 'client_id' => 0, 'enabled' => 1, 'version' => '1.5.0', 'author' => 'Luca De Caro'],
        ['extension_id' => 10100, 'package_id' => 10077, 'name' => 'Forms component', 'type' => 'component', 'element' => 'com_decaroforms', 'folder' => '', 'client_id' => 1, 'enabled' => 1, 'version' => '1.7.0', 'author' => 'Luca De Caro'],
        ['extension_id' => 10101, 'package_id' => 10077, 'name' => 'Forms system', 'type' => 'plugin', 'element' => 'decaroforms', 'folder' => 'system', 'client_id' => 0, 'enabled' => 1, 'version' => '1.7.0', 'author' => 'Luca De Caro'],
        ['extension_id' => 10110, 'package_id' => 10077, 'name' => 'Courses component', 'type' => 'component', 'element' => 'com_decarocourses', 'folder' => '', 'client_id' => 1, 'enabled' => 1, 'version' => '1.5.0', 'author' => 'Luca De Caro'],
        ['extension_id' => 10111, 'package_id' => 10077, 'name' => 'Courses analytics', 'type' => 'plugin', 'element' => 'decarocourses', 'folder' => 'xdecaroanalytics', 'client_id' => 0, 'enabled' => 0, 'version' => '1.5.0', 'author' => 'Luca De Caro'],
        ['extension_id' => 10112, 'package_id' => 10077, 'name' => 'Courses task', 'type' => 'plugin', 'element' => 'decarocourses', 'folder' => 'task', 'client_id' => 0, 'enabled' => 0, 'version' => '1.5.0', 'author' => 'Luca De Caro'],
    ];

    $service = $reflection->newInstanceWithoutConstructor();
    $resolver = $reflection->getMethod('resolvePackageChildren');
    $resolver->setAccessible(true);

    $formsChildren = $resolver->invoke($service, $extensions[0], $catalog['forms'], $extensions);
    $coursesChildren = $resolver->invoke($service, $extensions[1], $catalog['courses'], $extensions);
    $signature = static function (array $children): array {
        return array_map(static function (array $item): string {
            return $item['type'] . ':' . $item['folder'] . ':' . $item['element'];
        }, $children);
    };

    if ($signature($formsChildren) !== ['component::com_decaroforms', 'plugin:system:decaroforms']) {
        throw new \RuntimeException('Forms package children leaked extensions from another package.');
    }
    if ($signature($coursesChildren) !== ['component::com_decarocourses', 'plugin:xdecaroanalytics:decarocourses', 'plugin:task:decarocourses']) {
        throw new \RuntimeException('Courses package children were not recovered from its installed package manifest.');
    }

    $assetRegistry = json_decode(file_get_contents(__DIR__ . '/../src/com_xdecarocore/media/joomla.asset.json'), true);
    $assetTypes = [];
    foreach (($assetRegistry['assets'] ?? []) as $asset) {
        if (($asset['name'] ?? '') === 'com_xdecarocore.admin') {
            $assetTypes[$asset['type'] ?? ''] = $asset['uri'] ?? '';
        }
    }
    if (($assetTypes['style'] ?? '') !== 'com_xdecarocore/admin.css'
        || ($assetTypes['script'] ?? '') !== 'com_xdecarocore/admin.js'
        || !is_file(__DIR__ . '/../src/com_xdecarocore/media/js/admin.js')) {
        throw new \RuntimeException('Dashboard style/script Web Asset Manager URIs are incomplete or invalid.');
    }
    if (strpos($assetTypes['style'] ?? '', '/css/') !== false || strpos($assetTypes['script'] ?? '', '/js/') !== false) {
        throw new \RuntimeException('Dashboard Web Asset Manager URIs must not duplicate Joomla css/js media directories.');
    }

    $templates = [
        'default' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/default.php'),
        'products' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/products.php'),
        'extensions' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/extensions.php'),
        'updates' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/updates.php'),
        'diagnostics' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/diagnostics.php'),
        'information' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/information.php'),
    ];

    foreach ($templates as $name => $template) {
        if (strpos($template, "Text::_('COM_XDECAROCORE_SUITE')") === false) {
            throw new \RuntimeException('Suite eyebrow missing from dashboard layout: ' . $name);
        }
    }

    foreach (['products', 'extensions', 'updates', 'diagnostics', 'information'] as $name) {
        if (strpos($templates[$name], 'xdecaro-suite__hero') === false) {
            throw new \RuntimeException('Shared suite hero missing from dashboard layout: ' . $name);
        }
    }
    if (strpos($templates['information'], 'xdecaro-suite__page-header') !== false) {
        throw new \RuntimeException('Information must not use a separate page-header structure.');
    }

    $productsTemplate = $templates['products'];
    foreach (['data-xdecaro-package-toggle', 'xdecaro-suite__expansion-row', 'xdecaro-suite__child-table', 'COM_XDECAROCORE_ACTIONS', 'xdecaro-suite__action-cell', 'xdecaro-suite__responsive-table', 'data-label='] as $marker) {
        if (strpos($productsTemplate, $marker) === false) {
            throw new \RuntimeException('Package detail/action/responsive UI marker missing: ' . $marker);
        }
    }
    if (strpos($productsTemplate, '<td colspan="7"') === false) {
        throw new \RuntimeException('Expanded package details must span the dedicated Actions column.');
    }
    if (strpos($productsTemplate, "if (\$channel === 'stable') return 'xdecaro-badge--success';") === false) {
        throw new \RuntimeException('Stable product channels must use the success badge.');
    }
    if (strpos($productsTemplate, '$extensionLabel($child)') === false) {
        throw new \RuntimeException('Expanded package extension names must use readable labels.');
    }

    $extensionsTemplate = $templates['extensions'];
    if (strpos($extensionsTemplate, 'Text::_($name)') === false || strpos($extensionsTemplate, '$extensionLabel($extension)') === false) {
        throw new \RuntimeException('All Extensions must translate manifest language keys and provide a readable fallback.');
    }
    foreach (['extensions', 'updates'] as $name) {
        if (strpos($templates[$name], 'xdecaro-suite__responsive-table') === false || strpos($templates[$name], 'data-label=') === false) {
            throw new \RuntimeException('Narrow-container responsive table contract missing from dashboard layout: ' . $name);
        }
    }

    $dashboardCss = file_get_contents(__DIR__ . '/../src/com_xdecarocore/media/css/admin.css');
    foreach (['repeat(5, minmax(0, 1fr))', 'xdecaro-suite__diagnostic-row', 'xdecaro-suite__info-grid', 'xdecaro-suite__expansion-panel', 'container-name: xdecaro-suite', '@container xdecaro-suite (max-width: 38rem)', 'xdecaro-suite__responsive-table'] as $marker) {
        if (strpos($dashboardCss, $marker) === false) {
            throw new \RuntimeException('Dashboard responsive CSS marker missing: ' . $marker);
        }
    }

    foreach (glob($manifestRoot . '/packages/*.xml') ?: [] as $path) {
        unlink($path);
    }
    @rmdir($manifestRoot . '/packages');
    @rmdir($manifestRoot);

    echo "xdecaro Core dashboard catalog, package resolution and UI tests passed.\n";
}
