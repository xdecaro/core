<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$manifestPath = $root . '/package/pkg_core/pkg_core.xml';
$installerPath = $root . '/package/pkg_core/script.php';
$feedPath = $root . '/updates/pkg_core.xml';
$buildPythonPath = $root . '/build/build.py';
$buildShellPath = $root . '/build/build.sh';
$checksumPath = $root . '/build/update-feed-checksum.php';

foreach ([$manifestPath, $installerPath, $feedPath, $buildPythonPath, $buildShellPath, $checksumPath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing canonical Core package file: {$path}\n");
        exit(1);
    }
}

$manifest = (string) file_get_contents($manifestPath);
$installer = (string) file_get_contents($installerPath);
$feed = (string) file_get_contents($feedPath);
$buildPython = (string) file_get_contents($buildPythonPath);
$buildShell = (string) file_get_contents($buildShellPath);
$checksum = (string) file_get_contents($checksumPath);

$checks = [
    [$manifest, '<packagename>core</packagename>', 'Core package manifest must use packagename core.'],
    [$manifest, 'updates/pkg_core.xml', 'Core package manifest must register the canonical Joomla update feed.'],
    [$feed, '<element>pkg_core</element>', 'Core update feed must identify pkg_core.'],
    [$feed, 'pkg_core_', 'Core update feed must publish the canonical package ZIP.'],
    [$buildPython, 'pkg_core_', 'Core deterministic Python build must create pkg_core_<version>.zip.'],
    [$buildShell, 'package/pkg_core', 'Core shell build must read the canonical package source.'],
    [$buildShell, 'updates/pkg_core.xml', 'Core shell build must validate the canonical update feed.'],
    [$buildShell, 'pkg_core_', 'Core shell build must validate the canonical package ZIP.'],
    [$checksum, 'pkg_core_', 'Core checksum helper must hash the canonical package ZIP.'],
    [$checksum, 'updates/pkg_core.xml', 'Core checksum helper must update the canonical feed.'],
    [$installer, 'pkg_core', 'Core installer must know the canonical package identity.'],
    [$installer, 'pkg_xdecarocore', 'Core installer must temporarily recognize the legacy package identity during migration.'],
    [$installer, 'package_id', 'Core migration must verify canonical child ownership before retiring the legacy package.'],
];

foreach ($checks as [$haystack, $needle, $message]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

if (!str_contains($manifest, 'lib_xdecarocore.zip')
    || !str_contains($manifest, 'com_xdecarocore.zip')
    || !str_contains($manifest, 'plg_system_xdecarocore.zip')) {
    fwrite(STDERR, "Core child extension identities must remain unchanged.\n");
    exit(1);
}

echo "Core canonical package migration contract OK\n";
