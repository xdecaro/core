<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$manifestPath = $root . '/package/pkg_core/pkg_core.xml';
$installerPath = $root . '/package/pkg_core/script.php';
$feedPath = $root . '/updates/pkg_core.xml';
$buildPath = $root . '/build/build.py';

foreach ([$manifestPath, $installerPath, $feedPath, $buildPath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing canonical Core package file: {$path}\n");
        exit(1);
    }
}

$manifest = (string) file_get_contents($manifestPath);
$installer = (string) file_get_contents($installerPath);
$feed = (string) file_get_contents($feedPath);
$build = (string) file_get_contents($buildPath);

$checks = [
    [$manifest, '<packagename>core</packagename>', 'Core package manifest must use packagename core.'],
    [$manifest, 'updates/pkg_core.xml', 'Core package manifest must register the canonical Joomla update feed.'],
    [$feed, '<element>pkg_core</element>', 'Core update feed must identify pkg_core.'],
    [$feed, 'pkg_core_', 'Core update feed must publish the canonical package ZIP.'],
    [$build, 'pkg_core_', 'Core deterministic build must create pkg_core_<version>.zip.'],
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
