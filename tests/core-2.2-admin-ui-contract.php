<?php
/** Core 2.2.0 shared administrator UI contract. */
declare(strict_types=1);

$root = dirname(__DIR__);
$registryPath = $root . '/src/plg_system_xdecarocore/media/joomla.asset.json';
$assetServicePath = $root . '/src/lib_xdecarocore/src/Asset/AssetService.php';
$adminCssPath = $root . '/src/plg_system_xdecarocore/media/css/admin.css';
$fixturePath = $root . '/tests/fixtures/admin-ui-responsive.html';

$registry = json_decode((string) file_get_contents($registryPath), true, 512, JSON_THROW_ON_ERROR);
$assetService = (string) file_get_contents($assetServicePath);
$adminCss = is_file($adminCssPath) ? (string) file_get_contents($adminCssPath) : '';
$fixture = is_file($fixturePath) ? (string) file_get_contents($fixturePath) : '';

$fail = static function (string $message): never {
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
};

if (($registry['version'] ?? null) !== '2.2.0') {
    $fail('Core 2.2 public asset registry must be version 2.2.0.');
}

$assets = [];
foreach (($registry['assets'] ?? []) as $asset) {
    if (isset($asset['name'])) $assets[$asset['name']] = $asset;
}
foreach (['xdecaro.core', 'xdecaro.components', 'xdecaro.admin'] as $required) {
    if (!isset($assets[$required])) $fail("Missing public asset {$required}.");
}
if (($assets['xdecaro.components']['dependencies'] ?? null) !== ['xdecaro.core']) {
    $fail('xdecaro.components must depend on xdecaro.core.');
}
if (($assets['xdecaro.admin']['dependencies'] ?? null) !== ['xdecaro.components']) {
    $fail('xdecaro.admin must depend on xdecaro.components.');
}
if (!str_contains($assetService, "public const STYLE_ADMIN = 'xdecaro.admin'")) {
    $fail('AssetService::STYLE_ADMIN is missing.');
}
if (!str_contains($assetService, 'public function useAdminUi(WebAssetManager $webAssets): bool')) {
    $fail('AssetService::useAdminUi() is missing.');
}
if (!is_file($adminCssPath)) $fail('Public admin.css is missing from the system plugin.');

$requiredSelectors = [
    '.xdecaro-suite', '.xdecaro-suite__hero', '.xdecaro-suite__page-header', '.xdecaro-suite__eyebrow',
    '.xdecaro-suite__metrics', '.xdecaro-suite__metric', '.xdecaro-suite__section',
    '.xdecaro-suite__info-grid', '.xdecaro-suite__info-card', '.xdecaro-suite__diagnostic-list',
    '.xdecaro-suite__notice', '.xdecaro-suite__summary-bar', '.xdecaro-suite__responsive-wrap',
    '.xdecaro-suite__responsive-table', '.xdecaro-form', '.xdecaro-form-grid', '.xdecaro-form-grid--2',
    '.xdecaro-filterbar', '.xdecaro-accordion',
];
foreach ($requiredSelectors as $selector) {
    if (!str_contains($adminCss, $selector)) $fail("Missing public selector {$selector}.");
}

foreach (['.xdecaro-suite__products-table', '.xdecaro-suite__extensions-table', '.xdecaro-suite__updates-table'] as $privateSelector) {
    if (str_contains($adminCss, $privateSelector)) $fail("Core-specific selector {$privateSelector} leaked into public admin.css.");
}
if (preg_match('/(^|\})\s*body\s*\{/m', $adminCss) || str_contains($adminCss, '#header') || str_contains($adminCss, '.sidebar-wrapper')) {
    $fail('Public admin.css must not restyle global Joomla administrator chrome.');
}
foreach (['min-width: 0', 'max-width: 100%', 'overflow-wrap: anywhere'] as $safetyRule) {
    if (!str_contains($adminCss, $safetyRule)) $fail("Missing responsive safety rule {$safetyRule}.");
}
foreach (['.xdecaro-form .control-group', '.xdecaro-form .control-label', '.xdecaro-form .controls'] as $bridgeSelector) {
    if (!str_contains($adminCss, $bridgeSelector)) $fail("Missing scoped Joomla form bridge {$bridgeSelector}.");
}
if (preg_match('/(^|\})\s*\.xdecaro-suite__metric:last-child(?:\s|\{|,)/m', $adminCss)) {
    $fail('Generic metrics must not make the last metric span by default.');
}
if (!str_contains($adminCss, '.xdecaro-suite__metrics--fill-last')) $fail('Explicit fill-last metric modifier is missing.');
if (!str_contains($adminCss, '[data-label]') || !str_contains($adminCss, 'overflow-x: auto')) {
    $fail('Responsive table must support data-label stacking and wrapper-only horizontal scroll.');
}
foreach (['@container xdecaro-suite', '320px', '393px', '430px', '768px', '1024px'] as $responsiveMarker) {
    if (!str_contains($adminCss . $fixture, $responsiveMarker)) {
        $fail("Responsive verification marker missing: {$responsiveMarker}.");
    }
}

if ($fixture === '') $fail('Responsive acceptance fixture is missing.');
foreach (['xdecaro-suite__metrics', 'class="xdecaro-form"', 'class="control-group"', 'xdecaro-long-value', 'data-responsive="cards"', 'data-label="Versione"'] as $fixtureMarker) {
    if (!str_contains($fixture, $fixtureMarker)) $fail("Responsive fixture is incomplete: {$fixtureMarker}.");
}
if (substr_count($fixture, 'xdecaro-suite__metric') < 4) $fail('Responsive fixture must exercise four KPI cards.');

echo "PASS: Core 2.2 shared admin UI asset contract.\n";
