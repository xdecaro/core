<?php
/**
 * Dependency-free smoke test for the Core ecosystem dashboard catalog, UI assets and navigation.
 */

namespace Joomla\Database {
    interface DatabaseInterface {}
}

namespace {
    define('_JEXEC', 1);

    $root = dirname(__DIR__);
    $manifestRoot = sys_get_temp_dir() . '/xdecaro-core-dashboard-' . getmypid();
    @mkdir($manifestRoot . '/packages', 0777, true);
    define('JPATH_MANIFESTS', $manifestRoot);

    require_once $root . '/src/com_xdecarocore/admin/src/Service/EcosystemService.php';

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
        'core' => ['pkg_core', '2.1.0'],
        'forms' => ['pkg_decaroforms', '1.7.0'],
        'courses' => ['pkg_decarocourses', '1.5.0'],
        'competitions' => ['pkg_xdecarocompetitions', '1.3.0'],
        'documents' => ['pkg_decarodocuments', '1.3.0'],
        'membership' => ['pkg_decaromembership', '1.4.0'],
        'finance' => ['pkg_decarofinance', '1.3.0'],
        'protocol' => ['pkg_decaroprotocol', '1.5.0'],
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
        throw new \RuntimeException('Feedback must remain planned without an invented release version.');
    }

    $manifest = simplexml_load_file($root . '/src/com_xdecarocore/xdecarocore.xml');
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
        if (str_starts_with($link, 'index.php?')) {
            throw new \RuntimeException('Dashboard submenu link must not contain index.php?.');
        }
    }

    $adminFiles = [];
    foreach ($manifest->administration->files->children() as $file) {
        if ($file->getName() === 'filename') {
            $adminFiles[] = trim((string) $file);
        }
    }
    if (!in_array('config.xml', $adminFiles, true) || !is_file($root . '/src/com_xdecarocore/admin/config.xml')) {
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
        ['extension_id' => 10110, 'package_id' => 10078, 'name' => 'Courses component', 'type' => 'component', 'element' => 'com_decarocourses', 'folder' => '', 'client_id' => 1, 'enabled' => 1, 'version' => '1.5.0', 'author' => 'Luca De Caro'],
        ['extension_id' => 10111, 'package_id' => 10078, 'name' => 'Courses analytics', 'type' => 'plugin', 'element' => 'decarocourses', 'folder' => 'xdecaroanalytics', 'client_id' => 0, 'enabled' => 0, 'version' => '1.5.0', 'author' => 'Luca De Caro'],
        ['extension_id' => 10112, 'package_id' => 10078, 'name' => 'Courses task', 'type' => 'plugin', 'element' => 'decarocourses', 'folder' => 'task', 'client_id' => 0, 'enabled' => 0, 'version' => '1.5.0', 'author' => 'Luca De Caro'],
    ];

    $service = $reflection->newInstanceWithoutConstructor();
    $resolver = $reflection->getMethod('resolvePackageChildren');
    $resolver->setAccessible(true);
    $signature = static fn(array $children): array => array_map(
        static fn(array $item): string => $item['type'] . ':' . $item['folder'] . ':' . $item['element'],
        $children
    );

    if ($signature($resolver->invoke($service, $extensions[0], $catalog['forms'], $extensions))
        !== ['component::com_decaroforms', 'plugin:system:decaroforms']) {
        throw new \RuntimeException('Forms package children leaked extensions from another package.');
    }
    if ($signature($resolver->invoke($service, $extensions[1], $catalog['courses'], $extensions))
        !== ['component::com_decarocourses', 'plugin:xdecaroanalytics:decarocourses', 'plugin:task:decarocourses']) {
        throw new \RuntimeException('Courses package children were not recovered correctly.');
    }

    $assetRegistry = json_decode((string) file_get_contents($root . '/src/com_xdecarocore/media/joomla.asset.json'), true, 512, JSON_THROW_ON_ERROR);
    $assets = [];
    foreach (($assetRegistry['assets'] ?? []) as $asset) {
        $name = (string) ($asset['name'] ?? '');
        $type = (string) ($asset['type'] ?? '');
        if ($name !== '' && $type !== '') {
            $assets[$name . ':' . $type] = $asset;
        }
    }
    foreach ([
        'com_xdecarocore.admin:style' => 'com_xdecarocore/admin.css',
        'com_xdecarocore.admin:script' => 'com_xdecarocore/admin.js',
    ] as $key => $uri) {
        if (($assets[$key]['uri'] ?? '') !== $uri) {
            throw new \RuntimeException('Dashboard WAM entry is incomplete: ' . $key);
        }
    }
    if (isset($assets['com_xdecarocore.responsive:style'])) {
        throw new \RuntimeException('Legacy generic responsive asset must be retired.');
    }
    if (!is_file($root . '/src/com_xdecarocore/media/js/admin.js')) {
        throw new \RuntimeException('Dashboard script is missing.');
    }

    $templates = [];
    foreach (['default', 'products', 'extensions', 'updates', 'diagnostics', 'information', 'guide'] as $layout) {
        $templates[$layout] = (string) file_get_contents($root . '/src/com_xdecarocore/admin/tmpl/dashboard/' . $layout . '.php');
        if (!str_contains($templates[$layout], "Text::_('COM_XDECAROCORE_SUITE')")) {
            throw new \RuntimeException('Suite eyebrow missing from dashboard layout: ' . $layout);
        }
    }
    foreach (['products', 'extensions', 'updates', 'diagnostics', 'information', 'guide'] as $layout) {
        if (!str_contains($templates[$layout], 'xdecaro-suite__hero')) {
            throw new \RuntimeException('Shared suite hero missing from dashboard layout: ' . $layout);
        }
    }
    if (substr_count($templates['information'], 'xdecaro-suite__badge-slot') !== 3) {
        throw new \RuntimeException('Information status badges must remain compact.');
    }
    if (!str_contains($templates['default'], 'xdecaro-suite__responsive-table') || !str_contains($templates['default'], 'data-label=')) {
        throw new \RuntimeException('Dashboard summary must retain responsive-table markup.');
    }
    foreach (['extensions', 'updates'] as $layout) {
        if (!str_contains($templates[$layout], 'xdecaro-suite__responsive-table') || !str_contains($templates[$layout], 'data-label=')) {
            throw new \RuntimeException('Responsive table contract missing: ' . $layout);
        }
    }
    foreach (['data-xdecaro-package-toggle', 'xdecaro-suite__expansion-row', 'xdecaro-suite__child-table', 'xdecaro-suite__action-cell', 'xdecaro-suite__chevron'] as $marker) {
        if (!str_contains($templates['products'], $marker)) {
            throw new \RuntimeException('Package detail UI marker missing: ' . $marker);
        }
    }
    if (str_contains($templates['products'], '⌄')) {
        throw new \RuntimeException('Package toggle must not depend on a font chevron glyph.');
    }
    foreach (['xdecaro-suite__extension-element-mobile', 'xdecaro-suite__extension-element-cell'] as $marker) {
        if (!str_contains($templates['extensions'], $marker)) {
            throw new \RuntimeException('Extensions readable/mobile contract missing: ' . $marker);
        }
    }
    if (!str_contains($templates['updates'], 'xdecaro-suite__updates-table')
        || !str_contains($templates['updates'], 'xdecaro-suite__update-status-cell')) {
        throw new \RuntimeException('Updates compact responsive contract is missing.');
    }

    $publicCss = (string) file_get_contents($root . '/src/plg_system_xdecarocore/media/css/admin.css');
    foreach (['.xdecaro-suite {', '.xdecaro-suite__metrics', '.xdecaro-suite__info-grid', '.xdecaro-suite__diagnostic-row', '.xdecaro-form', '.xdecaro-filterbar', '.xdecaro-accordion', '@container xdecaro-suite', '393px'] as $marker) {
        if (!str_contains($publicCss, $marker)) {
            throw new \RuntimeException('Shared public UI marker missing: ' . $marker);
        }
    }

    $privateCss = (string) file_get_contents($root . '/src/com_xdecarocore/media/css/admin.css');
    foreach (['xdecaro-suite__products-table', 'xdecaro-suite__filter-button', 'xdecaro-suite__expansion-panel', 'xdecaro-suite__extension-element-mobile', 'xdecaro-suite__updates-table'] as $marker) {
        if (!str_contains($privateCss, $marker)) {
            throw new \RuntimeException('Private Core dashboard CSS marker missing: ' . $marker);
        }
    }
    foreach (['.xdecaro-form', '.xdecaro-filterbar', '.xdecaro-accordion', '.xdecaro-suite__metrics {', '.xdecaro-suite__info-grid {'] as $marker) {
        if (str_contains($privateCss, $marker)) {
            throw new \RuntimeException('Shared UI leaked back into private Core CSS: ' . $marker);
        }
    }

    $viewSource = (string) file_get_contents($root . '/src/com_xdecarocore/admin/src/View/Dashboard/HtmlView.php');
    foreach (['useAdminUi($webAssets)', 'ToolbarHelper::back(', "ToolbarHelper::preferences('com_xdecarocore')", 'ToolbarHelper::link(', 'COM_XDECAROCORE_GUIDE'] as $marker) {
        if (!str_contains($viewSource, $marker)) {
            throw new \RuntimeException('Dashboard toolbar/shared UI contract missing: ' . $marker);
        }
    }
    if (str_contains($viewSource, "useStyle('com_xdecarocore.responsive')")) {
        throw new \RuntimeException('Dashboard still loads the retired responsive asset.');
    }

    foreach (glob($manifestRoot . '/packages/*.xml') ?: [] as $path) {
        unlink($path);
    }
    @rmdir($manifestRoot . '/packages');
    @rmdir($manifestRoot);

    echo "xdecaro Core dashboard catalog, package resolution and shared UI tests passed.\n";
}
