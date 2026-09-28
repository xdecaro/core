<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$expectedVersion = '2.2.6';
$expectedSeries = '2.2';
$expectedJoomlaManifestTarget = '6.1.3';
$expectedJoomlaUpdateTarget = '6\\.1\\.3';
$expectedPhp = '8.3.0';

if (trim((string) file_get_contents($root . '/VERSION')) !== $expectedVersion) {
    throw new RuntimeException('VERSION must be 2.2.6.');
}
if (trim((string) file_get_contents($root . '/STABILIZATION_SERIES')) !== $expectedSeries) {
    throw new RuntimeException('STABILIZATION_SERIES must be 2.2.');
}

$versionSource = (string) file_get_contents($root . '/src/lib_xdecarocore/src/Version.php');
if (!str_contains($versionSource, "public const VERSION = '2.2.6';")) {
    throw new RuntimeException('Version::VERSION must be 2.2.6.');
}

$manifests = [
    'library' => $root . '/src/lib_xdecarocore/xdecarocore.xml',
    'component' => $root . '/src/com_xdecarocore/xdecarocore.xml',
    'plugin' => $root . '/src/plg_system_xdecarocore/xdecarocore.xml',
    'package' => $root . '/package/pkg_core/pkg_core.xml',
];

foreach ($manifests as $label => $path) {
    $xml = simplexml_load_file($path);
    if ($xml === false) {
        throw new RuntimeException('Invalid ' . $label . ' manifest.');
    }
    if (trim((string) $xml->version) !== $expectedVersion) {
        throw new RuntimeException($label . ' manifest must be 2.2.6.');
    }
    if (trim((string) $xml->targetplatform['version']) !== $expectedJoomlaManifestTarget) {
        throw new RuntimeException($label . ' manifest must target Joomla 6.1.3 exactly.');
    }
}

$package = simplexml_load_file($manifests['package']);
if ($package === false || trim((string) $package->packagename) !== 'core') {
    throw new RuntimeException('Canonical package identity must remain pkg_core/core.');
}

$installerScript = (string) file_get_contents($root . '/package/pkg_core/script.php');
if (!str_contains($installerScript, "protected \$minimumJoomla = '6.1.3';")) {
    throw new RuntimeException('Core package installer minimum Joomla must be 6.1.3.');
}
if (!str_contains($installerScript, "protected \$minimumPhp = '8.3.0';")) {
    throw new RuntimeException('Core package installer minimum PHP must be 8.3.0.');
}

foreach ([
    $root . '/src/plg_system_xdecarocore/media/joomla.asset.json',
    $root . '/src/com_xdecarocore/media/joomla.asset.json',
] as $assetPath) {
    $asset = json_decode((string) file_get_contents($assetPath), true, 512, JSON_THROW_ON_ERROR);
    if (($asset['version'] ?? '') !== $expectedVersion) {
        throw new RuntimeException(basename(dirname($assetPath)) . ' asset registry must be 2.2.6.');
    }
    foreach (($asset['assets'] ?? []) as $entry) {
        if (($entry['version'] ?? '') !== $expectedVersion) {
            throw new RuntimeException('Every Core Web Asset entry must be 2.2.6.');
        }
    }
}

$canonicalFeed = simplexml_load_file($root . '/updates/pkg_core.xml');
if ($canonicalFeed === false || !isset($canonicalFeed->update)) {
    throw new RuntimeException('Canonical pkg_core update feed is invalid.');
}
$canonical = $canonicalFeed->update;
if (trim((string) $canonical->element) !== 'pkg_core'
    || trim((string) $canonical->version) !== $expectedVersion
    || trim((string) $canonical->targetplatform['version']) !== $expectedJoomlaUpdateTarget
    || trim((string) $canonical->php_minimum) !== $expectedPhp) {
    throw new RuntimeException('Canonical update feed must publish Core 2.2.6 for Joomla 6.1.3 / PHP 8.3+.');
}
$download = trim((string) $canonical->downloads->downloadurl);
if (!str_ends_with($download, '/v2.2.6/pkg_core_2.2.6.zip')) {
    throw new RuntimeException('Canonical update feed download must point to pkg_core_2.2.6.zip.');
}

$legacyFeed = simplexml_load_file($root . '/updates/pkg_xdecarocore.xml');
if ($legacyFeed === false || !isset($legacyFeed->update)) {
    throw new RuntimeException('Legacy package migration feed is invalid.');
}
if (trim((string) $legacyFeed->update->element) !== 'pkg_xdecarocore') {
    throw new RuntimeException('Legacy update feed must remain a pkg_xdecarocore migration bridge.');
}

$catalogSource = (string) file_get_contents($root . '/src/com_xdecarocore/admin/src/Service/EcosystemService.php');
if (!str_contains($catalogSource, "'core' => ['name' => 'Core', 'package' => 'pkg_core', 'component' => 'com_xdecarocore', 'version' => '2.2.6'")) {
    throw new RuntimeException('Core dashboard catalog must identify Core 2.2.6 as current.');
}

foreach ([
    $root . '/src/plg_system_xdecarocore/media/css/core.css',
    $root . '/src/plg_system_xdecarocore/media/css/components.css',
    $root . '/src/plg_system_xdecarocore/media/css/admin.css',
    $root . '/src/com_xdecarocore/media/css/admin.css',
] as $cssPath) {
    $css = (string) file_get_contents($cssPath);
    if (preg_match('/Core by xdecaro 1\.[0-9.]+/', $css) === 1) {
        throw new RuntimeException('Stale 1.x CSS version comment remains in ' . $cssPath);
    }
}

echo "Core 2.2.6 release readiness contract passed.\n";
