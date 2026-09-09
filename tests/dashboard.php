<?php
/**
 * Dependency-free smoke test for the Core ecosystem dashboard catalog, UI assets, responsive
 * contracts and Joomla administrator navigation.
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
    if (!is_array($catalog) || count($catalog) < 20) {
        throw new \RuntimeException('Dashboard catalog is unexpectedly small.');
    }

    $required = [
        'core' => ['pkg_xdecarocore', '1.5.9'],
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
    if (($catalog['feedback']['channel'] ?? '') !== 'planned'
        || ($catalog['feedback']['package'] ?? '') !== 'pkg_xdecarofeedback'
        || ($catalog['feedback']['component'] ?? '') !== 'com_xdecarofeedback'
        || ($catalog['feedback']['version'] ?? '') !== '') {
        throw new \RuntimeException('Feedback must remain a planned product without an invented release version.');
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

    $adminFiles = [];
    foreach ($manifest->administration->files->children() as $file) {
        if ($file->getName() === 'filename') {
            $adminFiles[] = trim((string) $file);
        }
    }
    if (!in_array('config.xml', $adminFiles, true) || !is_file(__DIR__ . '/../src/com_xdecarocore/admin/config.xml')) {
        throw new \RuntimeException('Native Joomla component Options configuration is not packaged.');
    }

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
    $assets = [];
    foreach (($assetRegistry['assets'] ?? []) as $asset) {
        $name = (string) ($asset['name'] ?? '');
        $type = (string) ($asset['type'] ?? '');
        if ($name !== '' && $type !== '') {
            $assets[$name . ':' . $type] = $asset;
        }
    }

    $expectedAssets = [
        'com_xdecarocore.admin:style' => 'com_xdecarocore/admin.css',
        'com_xdecarocore.responsive:style' => 'com_xdecarocore/responsive.css',
        'com_xdecarocore.admin:script' => 'com_xdecarocore/admin.js',
    ];
    foreach ($expectedAssets as $key => $uri) {
        if (($assets[$key]['uri'] ?? '') !== $uri) {
            throw new \RuntimeException('Dashboard Web Asset Manager entry is incomplete: ' . $key);
        }
        if (strpos($uri, '/css/') !== false || strpos($uri, '/js/') !== false) {
            throw new \RuntimeException('Dashboard Web Asset Manager URI duplicates a Joomla media type directory.');
        }
    }
    if (($assets['com_xdecarocore.responsive:style']['dependencies'][0] ?? '') !== 'com_xdecarocore.admin') {
        throw new \RuntimeException('Responsive dashboard style must depend on the base administrator style.');
    }
    if (!is_file(__DIR__ . '/../src/com_xdecarocore/media/css/responsive.css')
        || !is_file(__DIR__ . '/../src/com_xdecarocore/media/js/admin.js')) {
        throw new \RuntimeException('Dashboard responsive style/script files are missing.');
    }

    $templates = [
        'default' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/default.php'),
        'products' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/products.php'),
        'extensions' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/extensions.php'),
        'updates' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/updates.php'),
        'diagnostics' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/diagnostics.php'),
        'information' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/information.php'),
        'guide' => file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/tmpl/dashboard/guide.php'),
    ];

    foreach ($templates as $name => $template) {
        if (strpos($template, "Text::_('COM_XDECAROCORE_SUITE')") === false) {
            throw new \RuntimeException('Suite eyebrow missing from dashboard layout: ' . $name);
        }
    }

    foreach (['products', 'extensions', 'updates', 'diagnostics', 'information', 'guide'] as $name) {
        if (strpos($templates[$name], 'xdecaro-suite__hero') === false) {
            throw new \RuntimeException('Shared suite hero missing from dashboard layout: ' . $name);
        }
    }
    if (strpos($templates['information'], 'xdecaro-suite__page-header') !== false) {
        throw new \RuntimeException('Information must not use a separate page-header structure.');
    }
    if (substr_count($templates['information'], 'xdecaro-suite__badge-slot') !== 3) {
        throw new \RuntimeException('Information card status badges must remain wrapped in compact badge slots.');
    }
    if (strpos($templates['guide'], 'COM_XDECAROCORE_GUIDE_TOOLBAR_TITLE') === false
        || strpos($templates['guide'], 'COM_XDECAROCORE_GUIDE_PACKAGE_CONTENTS') === false) {
        throw new \RuntimeException('Core Guide content is incomplete.');
    }

    if (strpos($templates['default'], 'xdecaro-suite__dashboard-products-table') === false
        || strpos($templates['default'], 'xdecaro-suite__responsive-table') === false
        || strpos($templates['default'], 'data-label=') === false) {
        throw new \RuntimeException('Dashboard product summary must share the responsive card contract.');
    }

    $productsTemplate = $templates['products'];
    foreach (['data-xdecaro-package-toggle', 'xdecaro-suite__expansion-row', 'xdecaro-suite__child-table', 'COM_XDECAROCORE_ACTIONS', 'xdecaro-suite__action-cell', 'xdecaro-suite__responsive-table', 'data-label=', 'xdecaro-suite__warning-compact'] as $marker) {
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
    foreach (['Text::_($name)', '$extensionLabel($extension)', 'xdecaro-suite__extension-element-mobile', 'xdecaro-suite__extension-element-cell'] as $marker) {
        if (strpos($extensionsTemplate, $marker) === false) {
            throw new \RuntimeException('All Extensions compact/readable contract missing: ' . $marker);
        }
    }

    if (strpos($templates['updates'], 'xdecaro-suite__updates-table') === false
        || strpos($templates['updates'], 'xdecaro-suite__update-status-cell') === false) {
        throw new \RuntimeException('Updates compact responsive contract is missing.');
    }

    foreach (['extensions', 'updates'] as $name) {
        if (strpos($templates[$name], 'xdecaro-suite__responsive-table') === false || strpos($templates[$name], 'data-label=') === false) {
            throw new \RuntimeException('Responsive table contract missing from dashboard layout: ' . $name);
        }
    }

    $baseCss = file_get_contents(__DIR__ . '/../src/com_xdecarocore/media/css/admin.css');
    foreach (['repeat(5, minmax(0, 1fr))', 'xdecaro-suite__diagnostic-row', 'xdecaro-suite__info-grid', 'xdecaro-suite__expansion-panel', 'container-name: xdecaro-suite', 'xdecaro-suite__responsive-table'] as $marker) {
        if (strpos($baseCss, $marker) === false) {
            throw new \RuntimeException('Dashboard base CSS marker missing: ' . $marker);
        }
    }

    $responsiveCss = file_get_contents(__DIR__ . '/../src/com_xdecarocore/media/css/responsive.css');
    foreach (['@container xdecaro-suite (max-width: 55rem)', '@container xdecaro-suite (max-width: 20rem)', 'min-width: 0', 'max-width: 100%', 'grid-template-columns: repeat(2, minmax(0, 1fr))', '.xdecaro-suite__metric:last-child', 'safe-area-inset-top', 'safe-area-inset-bottom', 'xdecaro-suite__warning-compact', 'padding: var(--xdecaro-space-3, 0.75rem);'] as $marker) {
        if (strpos($responsiveCss, $marker) === false) {
            throw new \RuntimeException('Dashboard 1.5.9 responsive CSS marker missing: ' . $marker);
        }
    }

    $viewSource = file_get_contents(__DIR__ . '/../src/com_xdecarocore/admin/src/View/Dashboard/HtmlView.php');
    foreach (["useStyle('com_xdecarocore.responsive')", 'ToolbarHelper::back(', "ToolbarHelper::preferences('com_xdecarocore')", 'ToolbarHelper::link(', 'COM_XDECAROCORE_GUIDE'] as $marker) {
        if (strpos($viewSource, $marker) === false) {
            throw new \RuntimeException('Native dashboard toolbar/responsive asset contract missing: ' . $marker);
        }
    }

    foreach (glob($manifestRoot . '/packages/*.xml') ?: [] as $path) {
        unlink($path);
    }
    @rmdir($manifestRoot . '/packages');
    @rmdir($manifestRoot);

    echo "xdecaro Core dashboard catalog, package resolution, toolbar, guide and responsive UI tests passed.\n";
}
