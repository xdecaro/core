<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$registryPath = $root . '/src/plg_system_xdecarocore/media/joomla.asset.json';
$servicePath = $root . '/src/lib_xdecarocore/src/Asset/AssetService.php';
$adminCssPath = $root . '/src/plg_system_xdecarocore/media/css/admin.css';
$fixturePath = $root . '/tests/fixtures/admin-ui-contract.html';
$viewPath = $root . '/src/com_xdecarocore/admin/src/View/Dashboard/HtmlView.php';
$componentRegistryPath = $root . '/src/com_xdecarocore/media/joomla.asset.json';
$componentCssPath = $root . '/src/com_xdecarocore/media/css/admin.css';
$componentResponsivePath = $root . '/src/com_xdecarocore/media/css/responsive.css';
$dashboardTemplatePath = $root . '/src/com_xdecarocore/admin/tmpl/dashboard/default.php';
$productsTemplatePath = $root . '/src/com_xdecarocore/admin/tmpl/dashboard/products.php';
$extensionsTemplatePath = $root . '/src/com_xdecarocore/admin/tmpl/dashboard/extensions.php';
$updatesTemplatePath = $root . '/src/com_xdecarocore/admin/tmpl/dashboard/updates.php';
$informationTemplatePath = $root . '/src/com_xdecarocore/admin/tmpl/dashboard/information.php';

$registry = json_decode((string) file_get_contents($registryPath), true, 512, JSON_THROW_ON_ERROR);
$assets = $registry['assets'] ?? [];
$styles = [];

foreach ($assets as $asset) {
    if (($asset['type'] ?? null) === 'style' && isset($asset['name'])) {
        $styles[$asset['name']] = $asset;
    }
}

foreach (['xdecaro.core', 'xdecaro.components', 'xdecaro.admin'] as $required) {
    if (!isset($styles[$required])) {
        throw new RuntimeException('Missing public Core style asset: ' . $required);
    }
}

if (($styles['xdecaro.components']['dependencies'] ?? []) !== ['xdecaro.core']) {
    throw new RuntimeException('xdecaro.components must depend only on xdecaro.core.');
}

if (($styles['xdecaro.admin']['dependencies'] ?? []) !== ['xdecaro.components']) {
    throw new RuntimeException('xdecaro.admin must depend only on xdecaro.components.');
}

$service = (string) file_get_contents($servicePath);

if (!str_contains($service, "public const STYLE_ADMIN = 'xdecaro.admin';")) {
    throw new RuntimeException('AssetService::STYLE_ADMIN is missing.');
}

if (!str_contains($service, 'public function useAdminUi(WebAssetManager $webAssets): bool')) {
    throw new RuntimeException('AssetService::useAdminUi() is missing.');
}

if (!str_contains($service, 'useStyle(self::STYLE_ADMIN)')) {
    throw new RuntimeException('useAdminUi() must enable STYLE_ADMIN through Web Asset Manager.');
}

$adminCss = is_file($adminCssPath) ? (string) file_get_contents($adminCssPath) : '';
$publicSelectors = [
    '.xdecaro-suite',
    '.xdecaro-suite__hero',
    '.xdecaro-suite__page-header',
    '.xdecaro-suite__eyebrow',
    '.xdecaro-suite__metrics',
    '.xdecaro-suite__metric',
    '.xdecaro-suite__section',
    '.xdecaro-suite__actions',
    '.xdecaro-suite__info-grid',
    '.xdecaro-suite__info-card',
    '.xdecaro-suite__diagnostic-list',
    '.xdecaro-suite__notice',
    '.xdecaro-suite__summary-bar',
    '.xdecaro-suite__responsive-wrap',
    '.xdecaro-suite__responsive-table',
    '.xdecaro-form',
    '.xdecaro-form-grid',
    '.xdecaro-form-grid--2',
    '.xdecaro-filterbar',
    '.xdecaro-accordion',
];

foreach ($publicSelectors as $selector) {
    if (!str_contains($adminCss, $selector)) {
        throw new RuntimeException('Missing public admin selector: ' . $selector);
    }
}

foreach (['.xdecaro-suite__products-table', '.xdecaro-suite__extensions-table', '.xdecaro-suite__updates-table'] as $privateSelector) {
    if (str_contains($adminCss, $privateSelector)) {
        throw new RuntimeException('Core dashboard-private selector leaked into public admin CSS: ' . $privateSelector);
    }
}

$globalSelectorPatterns = [
    '/(^|\n)\s*body(?:\s|\.|#|\[|:|\{)/i' => 'body',
    '/(^|\n|,)\s*#header\b/i' => '#header',
    '/(^|\n|,)\s*\.sidebar-wrapper\b/i' => '.sidebar-wrapper',
];
foreach ($globalSelectorPatterns as $pattern => $label) {
    if (preg_match($pattern, $adminCss) === 1) {
        throw new RuntimeException('Public admin CSS contains an unscoped Joomla/global selector: ' . $label);
    }
}

$requiredFragments = [
    'container-type: inline-size',
    'min-width: 0',
    'max-width: 100%',
    'overflow-wrap: anywhere',
    '.xdecaro-form .control-group',
    '.xdecaro-form .control-label',
    '.xdecaro-form .controls',
    '.xdecaro-suite__metrics--fill-last',
    'overflow-x: auto',
    'td[data-label]::before',
    '@container xdecaro-suite',
];

foreach ($requiredFragments as $fragment) {
    if (!str_contains($adminCss, $fragment)) {
        throw new RuntimeException('Shared admin responsive contract missing fragment: ' . $fragment);
    }
}

if (str_contains($adminCss, '.xdecaro-suite__metric:last-child')) {
    throw new RuntimeException('Public KPI grid must not make the final metric span by default.');
}

if (!is_file($fixturePath)) {
    throw new RuntimeException('Responsive admin UI fixture is missing.');
}

$fixture = (string) file_get_contents($fixturePath);
foreach (['data-contract-width="320"', 'data-contract-width="393"', 'data-contract-width="430"', 'data-contract-width="768"', 'data-contract-width="1024"', 'data-label=', 'xdecaro-form', 'xdecaro-accordion'] as $needle) {
    if (!str_contains($fixture, $needle)) {
        throw new RuntimeException('Responsive fixture missing contract marker: ' . $needle);
    }
}

$viewSource = (string) file_get_contents($viewPath);
if (!str_contains($viewSource, '(new AssetService())->useAdminUi($webAssets);')) {
    throw new RuntimeException('Core dashboard must consume the public admin UI through AssetService::useAdminUi().');
}
if (str_contains($viewSource, "useStyle('com_xdecarocore.responsive')")) {
    throw new RuntimeException('Core dashboard must not require the legacy generic responsive asset.');
}

$componentRegistry = json_decode((string) file_get_contents($componentRegistryPath), true, 512, JSON_THROW_ON_ERROR);
foreach (($componentRegistry['assets'] ?? []) as $asset) {
    if (($asset['name'] ?? '') === 'com_xdecarocore.responsive') {
        throw new RuntimeException('Component registry must not expose the legacy generic responsive asset.');
    }
}

$componentCss = (string) file_get_contents($componentCssPath);
$privateFamilies = [
    '.xdecaro-suite__products-table',
    '.xdecaro-suite__product-row',
    '.xdecaro-suite__extensions-table',
    '.xdecaro-suite__extension-primary',
    '.xdecaro-suite__extension-element-mobile',
    '.xdecaro-suite__extension-element-cell',
    '.xdecaro-suite__updates-table',
    '.xdecaro-suite__update-status-cell',
    '.xdecaro-suite__dashboard-metrics',
    '.xdecaro-suite__dashboard-products-table',
    '.xdecaro-suite__filter-button',
    '.xdecaro-suite__expansion-panel',
];
foreach ($privateFamilies as $selector) {
    if (!str_contains($componentCss, $selector)) {
        throw new RuntimeException('Core dashboard-private CSS selector is missing: ' . $selector);
    }
}

foreach (['.xdecaro-suite__hero {', '.xdecaro-suite__metrics {', '.xdecaro-suite__info-grid {', '.xdecaro-suite__diagnostic-list {', '.xdecaro-suite__notice {', '.xdecaro-suite__summary-bar {', '.xdecaro-suite__definition-list {'] as $genericDuplicate) {
    if (str_contains($componentCss, $genericDuplicate)) {
        throw new RuntimeException('Generic shared UI rule remains duplicated in component CSS: ' . $genericDuplicate);
    }
}

if (is_file($componentResponsivePath)) {
    throw new RuntimeException('Legacy component responsive.css should be removed after public/private classification.');
}

$dashboardTemplate = (string) file_get_contents($dashboardTemplatePath);
foreach ([
    'xdecaro-suite__metrics xdecaro-suite__metrics--fill-last xdecaro-suite__dashboard-metrics',
    'xdecaro-suite__responsive-wrap xdecaro-suite__responsive-wrap--stack',
    'xdecaro-suite__action-heading',
    'xdecaro-suite__action-cell',
] as $marker) {
    if (!str_contains($dashboardTemplate, $marker)) {
        throw new RuntimeException('Core dashboard mobile-card contract missing marker: ' . $marker);
    }
}

foreach ([
    'grid-template-columns: repeat(5, minmax(0, 1fr))',
    '.xdecaro-suite__dashboard-metrics.xdecaro-suite__metrics--fill-last > :last-child',
    'grid-column: auto',
    '.xdecaro-suite__dashboard-products-table > tbody > tr',
    '.xdecaro-suite__dashboard-products-table .xdecaro-suite__action-cell .xdecaro-button',
    'min-height: 2.75rem',
    'white-space: nowrap',
] as $fragment) {
    if (!str_contains($componentCss, $fragment)) {
        throw new RuntimeException('Core dashboard responsive card CSS missing fragment: ' . $fragment);
    }
}

$productsTemplate = (string) file_get_contents($productsTemplatePath);
if (substr_count($productsTemplate, 'xdecaro-suite__responsive-wrap--stack') < 2) {
    throw new RuntimeException('Components view must stack both the product table and nested child-extension table on narrow layouts.');
}

foreach ([
    'All Extensions' => $extensionsTemplatePath,
    'Updates' => $updatesTemplatePath,
] as $viewLabel => $templatePath) {
    $template = (string) file_get_contents($templatePath);
    if (!str_contains($template, 'xdecaro-suite__responsive-wrap--stack')) {
        throw new RuntimeException($viewLabel . ' view must opt into stacked mobile cards.');
    }
}

$informationTemplate = (string) file_get_contents($informationTemplatePath);
if (!str_contains($informationTemplate, '<code>pkg_core</code>')) {
    throw new RuntimeException('Information must display the canonical Core package identity pkg_core.');
}
if (str_contains($informationTemplate, '<code>pkg_xdecarocore</code>')) {
    throw new RuntimeException('Information must not display the retired pkg_xdecarocore identity.');
}

echo "Core 2.2 shared admin UI contract passed.\n";
