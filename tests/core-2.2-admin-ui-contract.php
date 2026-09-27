<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$registryPath = $root . '/src/plg_system_xdecarocore/media/joomla.asset.json';
$servicePath = $root . '/src/lib_xdecarocore/src/Asset/AssetService.php';

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

echo "Core 2.2 shared admin UI asset contract passed.\n";
