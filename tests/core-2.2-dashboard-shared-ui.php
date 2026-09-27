<?php
/** Core 2.2 dashboard migration contract. */
declare(strict_types=1);

$root = dirname(__DIR__);
$view = (string) file_get_contents($root . '/src/com_xdecarocore/admin/src/View/Dashboard/HtmlView.php');
$registry = json_decode((string) file_get_contents($root . '/src/com_xdecarocore/media/joomla.asset.json'), true, 512, JSON_THROW_ON_ERROR);
$privateCss = (string) file_get_contents($root . '/src/com_xdecarocore/media/css/admin.css');

$fail = static function (string $message): never {
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
};

if (!str_contains($view, '(new AssetService())->useAdminUi($webAssets);')) {
    $fail('Dashboard must consume AssetService::useAdminUi().');
}
if (str_contains($view, "useStyle('com_xdecarocore.responsive')")) {
    $fail('Dashboard must not require the legacy generic responsive style.');
}
if (str_contains($view, '(new AssetService())->useComponents($webAssets);')) {
    $fail('Dashboard must not stop at the lower-level components asset.');
}

$styles = [];
foreach (($registry['assets'] ?? []) as $asset) {
    if (($asset['type'] ?? '') === 'style') {
        $styles[$asset['name'] ?? ''] = $asset;
    }
}
if (!isset($styles['com_xdecarocore.admin'])) {
    $fail('Core-specific dashboard style must remain registered.');
}
if (isset($styles['com_xdecarocore.responsive'])) {
    $fail('Legacy component responsive asset must be retired after public UI migration.');
}

foreach (['.xdecaro-suite__products-table', '.xdecaro-suite__extensions-table', '.xdecaro-suite__updates-table'] as $privateSelector) {
    if (!str_contains($privateCss, $privateSelector)) {
        $fail("Core-specific selector {$privateSelector} must remain private.");
    }
}

foreach (['.xdecaro-form', '.xdecaro-filterbar', '.xdecaro-accordion'] as $publicSelector) {
    if (str_contains($privateCss, $publicSelector)) {
        $fail("Shared selector {$publicSelector} must not be reimplemented in Core component CSS.");
    }
}

echo "PASS: Core dashboard consumes shared admin UI and keeps only private styling.\n";
