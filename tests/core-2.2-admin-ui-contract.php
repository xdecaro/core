<?php
/**
 * Core 2.2.0 shared administrator UI contract.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

$registryPath = $root . '/src/plg_system_xdecarocore/media/joomla.asset.json';
$assetServicePath = $root . '/src/lib_xdecarocore/src/Asset/AssetService.php';
$adminCssPath = $root . '/src/plg_system_xdecarocore/media/css/admin.css';

$registry = json_decode((string) file_get_contents($registryPath), true, 512, JSON_THROW_ON_ERROR);
$assetService = (string) file_get_contents($assetServicePath);

$fail = static function (string $message): never {
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
};

if (($registry['version'] ?? null) !== '2.1.0' && ($registry['version'] ?? null) !== '2.2.0') {
    $fail('Unexpected Core asset registry baseline version.');
}

$assets = [];
foreach (($registry['assets'] ?? []) as $asset) {
    if (isset($asset['name'])) {
        $assets[$asset['name']] = $asset;
    }
}

foreach (['xdecaro.core', 'xdecaro.components'] as $required) {
    if (!isset($assets[$required])) {
        $fail("Missing existing public asset {$required}.");
    }
}

if (($assets['xdecaro.components']['dependencies'] ?? null) !== ['xdecaro.core']) {
    $fail('xdecaro.components must continue to depend on xdecaro.core.');
}

if (!isset($assets['xdecaro.admin'])) {
    $fail('Missing public xdecaro.admin asset.');
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

if (!is_file($adminCssPath)) {
    $fail('Public admin.css is missing from the system plugin.');
}

echo "PASS: Core 2.2 shared admin UI asset contract.\n";
