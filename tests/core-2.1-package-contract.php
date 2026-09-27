<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));
$series = trim((string) file_get_contents($root . '/STABILIZATION_SERIES'));

if (version_compare($version, '2.1.0', '<')) {
    fwrite(STDERR, "Canonical pkg_core contract requires Core 2.1.0 or newer.\n");
    exit(1);
}

$parts = explode('.', $version);
$expectedSeries = ($parts[0] ?? '') . '.' . ($parts[1] ?? '');
if ($series !== $expectedSeries) {
    fwrite(STDERR, "STABILIZATION_SERIES must match the current Core major.minor release line.\n");
    exit(1);
}

$package = simplexml_load_file($root . '/package/pkg_core/pkg_core.xml');
if ($package === false
    || trim((string) $package->packagename) !== 'core'
    || trim((string) $package->version) !== $version) {
    fwrite(STDERR, "Canonical Core package identity/version is invalid.\n");
    exit(1);
}

$versionedFiles = [
    $root . '/src/lib_xdecarocore/xdecarocore.xml',
    $root . '/src/com_xdecarocore/xdecarocore.xml',
    $root . '/src/plg_system_xdecarocore/xdecarocore.xml',
    $root . '/package/pkg_core/pkg_core.xml',
    $root . '/updates/pkg_core.xml',
    $root . '/src/lib_xdecarocore/src/Version.php',
    $root . '/src/plg_system_xdecarocore/media/joomla.asset.json',
    $root . '/src/com_xdecarocore/media/joomla.asset.json',
];

foreach ($versionedFiles as $path) {
    $text = (string) file_get_contents($path);
    if (!str_contains($text, $version)) {
        fwrite(STDERR, "Core {$version} version missing from {$path}.\n");
        exit(1);
    }
}

$release = (string) file_get_contents($root . '/.github/workflows/release.yml');
foreach (['pkg_core_${VERSION}.zip', 'updates/pkg_core.xml', 'updates/pkg_xdecarocore.xml'] as $needle) {
    if (!str_contains($release, $needle)) {
        fwrite(STDERR, "Core release workflow missing canonical package contract: {$needle}\n");
        exit(1);
    }
}

$runtime = (string) file_get_contents($root . '/.github/workflows/runtime-smoke.yml');
foreach (['pkg_core_${VERSION}.zip', 'pkg_xdecarocore_2.0.1.zip', "element='pkg_core'", "element='pkg_xdecarocore'", 'package_id'] as $needle) {
    if (!str_contains($runtime, $needle)) {
        fwrite(STDERR, "Core runtime workflow missing preserved package migration assertion: {$needle}\n");
        exit(1);
    }
}

echo "Core {$version} canonical pkg_core contract OK\n";
