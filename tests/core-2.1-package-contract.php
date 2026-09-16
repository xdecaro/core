<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));
$series = trim((string) file_get_contents($root . '/STABILIZATION_SERIES'));

if ($version !== '2.1.0' || $series !== '2.1') {
    fwrite(STDERR, "Core canonical package release must start at 2.1.0 / series 2.1.\n");
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
    if (!str_contains($text, '2.1.0')) {
        fwrite(STDERR, "Core 2.1.0 version missing from {$path}.\n");
        exit(1);
    }
}

$release = (string) file_get_contents($root . '/.github/workflows/release.yml');
foreach (["2.1.*", 'pkg_core_${VERSION}.zip', 'updates/pkg_core.xml'] as $needle) {
    if (!str_contains($release, $needle)) {
        fwrite(STDERR, "Core release workflow missing canonical 2.1 contract: {$needle}\n");
        exit(1);
    }
}

$runtime = (string) file_get_contents($root . '/.github/workflows/runtime-smoke.yml');
foreach (['pkg_core_${VERSION}.zip', 'pkg_xdecarocore_2.0.1.zip', "element='pkg_core'", "element='pkg_xdecarocore'", 'package_id'] as $needle) {
    if (!str_contains($runtime, $needle)) {
        fwrite(STDERR, "Core runtime workflow missing migration assertion: {$needle}\n");
        exit(1);
    }
}

echo "Core 2.1 canonical release contract OK\n";
