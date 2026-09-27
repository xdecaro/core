<?php
/**
 * Historical Core 2.1 canonical-package migration contract.
 *
 * This test intentionally survives later 2.x releases: it protects the package identity
 * established by 2.1 without forcing the current release to remain version 2.1.0.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));

if (version_compare($version, '2.1.0', '<') || !str_starts_with($version, '2.')) {
    fwrite(STDERR, "Core canonical pkg_core contract requires Core 2.1.0 or later in the 2.x line.\n");
    exit(1);
}

$manifest = (string) file_get_contents($root . '/package/pkg_core/pkg_core.xml');
$installer = (string) file_get_contents($root . '/package/pkg_core/script.php');
$canonicalFeed = (string) file_get_contents($root . '/updates/pkg_core.xml');
$legacyFeed = (string) file_get_contents($root . '/updates/pkg_xdecarocore.xml');
$release = (string) file_get_contents($root . '/.github/workflows/release.yml');
$runtime = (string) file_get_contents($root . '/.github/workflows/runtime-smoke.yml');

$checks = [
    [$manifest, '<packagename>core</packagename>', 'Canonical package manifest lost packagename core.'],
    [$manifest, 'lib_xdecarocore.zip', 'Canonical package lost library child.'],
    [$manifest, 'com_xdecarocore.zip', 'Canonical package lost component child.'],
    [$manifest, 'plg_system_xdecarocore.zip', 'Canonical package lost system plugin child.'],
    [$installer, 'pkg_core', 'Installer no longer recognizes canonical pkg_core.'],
    [$installer, 'pkg_xdecarocore', 'Installer no longer recognizes legacy package migration source.'],
    [$installer, 'package_id', 'Installer no longer verifies package child ownership.'],
    [$canonicalFeed, '<element>pkg_core</element>', 'Canonical feed no longer identifies pkg_core.'],
    [$legacyFeed, '<element>pkg_core</element>', 'Legacy bridge must resolve clients to canonical pkg_core.'],
    [$release, 'pkg_core_${VERSION}.zip', 'Release workflow no longer publishes canonical pkg_core ZIP.'],
    [$release, 'updates/pkg_core.xml', 'Release workflow no longer publishes canonical update feed.'],
    [$release, 'updates/pkg_xdecarocore.xml', 'Release workflow no longer maintains legacy update bridge.'],
    [$runtime, 'pkg_core_${VERSION}.zip', 'Runtime workflow no longer installs the canonical package.'],
];

foreach ($checks as [$haystack, $needle, $message]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Core 2.1 canonical package migration guarantees preserved for Core {$version}.\n";
