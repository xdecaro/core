<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$registryPath = $root . '/src/plg_system_xdecarocore/media/joomla.asset.json';
$servicePath = $root . '/src/lib_xdecarocore/src/Asset/AssetService.php';
$adminCssPath = $root . '/src/plg_system_xdecarocore/media/css/admin.css';
$fixturePath = $root . '/tests/fixtures/admin-ui-contract.html';

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

foreach (['body {', '#header', '.sidebar-wrapper'] as $globalLeak) {
    if (str_contains($adminCss, $globalLeak)) {
        throw new RuntimeException('Public admin CSS contains an unscoped Joomla/global rule: ' . $globalLeak);
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

echo "Core 2.2 shared admin UI contract passed.\n";
