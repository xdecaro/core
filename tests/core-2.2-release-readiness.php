<?php
/** Core 2.2.0 release metadata contract. */
declare(strict_types=1);

$root = dirname(__DIR__);
$fail = static function (string $message): never {
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
};

if (trim((string) file_get_contents($root . '/VERSION')) !== '2.2.0') {
    $fail('VERSION must be 2.2.0.');
}
if (trim((string) file_get_contents($root . '/STABILIZATION_SERIES')) !== '2.2') {
    $fail('STABILIZATION_SERIES must be 2.2.');
}

$versionSource = (string) file_get_contents($root . '/src/lib_xdecarocore/src/Version.php');
if (!str_contains($versionSource, "public const VERSION = '2.2.0'")) {
    $fail('Version::VERSION must be 2.2.0.');
}

$manifestPaths = [
    'library' => 'src/lib_xdecarocore/xdecarocore.xml',
    'component' => 'src/com_xdecarocore/xdecarocore.xml',
    'plugin' => 'src/plg_system_xdecarocore/xdecarocore.xml',
    'package' => 'package/pkg_core/pkg_core.xml',
];
foreach ($manifestPaths as $label => $path) {
    $xml = simplexml_load_file($root . '/' . $path);
    if ($xml === false) {
        $fail("Invalid {$label} manifest.");
    }
    if (trim((string) $xml->version) !== '2.2.0') {
        $fail("{$label} manifest version must be 2.2.0.");
    }
    if (trim((string) $xml->targetplatform['version']) !== '6.1.3') {
        $fail("{$label} manifest must target Joomla 6.1.3 exactly.");
    }
}

$package = simplexml_load_file($root . '/package/pkg_core/pkg_core.xml');
if ($package === false || trim((string) $package->packagename) !== 'core') {
    $fail('Canonical package identity must remain pkg_core/core.');
}

foreach ([
    'src/plg_system_xdecarocore/media/joomla.asset.json',
    'src/com_xdecarocore/media/joomla.asset.json',
] as $registryPath) {
    $registry = json_decode((string) file_get_contents($root . '/' . $registryPath), true, 512, JSON_THROW_ON_ERROR);
    if (($registry['version'] ?? null) !== '2.2.0') {
        $fail("Asset registry {$registryPath} must be 2.2.0.");
    }
    foreach (($registry['assets'] ?? []) as $asset) {
        if (($asset['version'] ?? null) !== '2.2.0') {
            $fail("Every asset in {$registryPath} must be version 2.2.0.");
        }
    }
}

foreach (['updates/pkg_core.xml', 'updates/pkg_xdecarocore.xml'] as $feedPath) {
    $feed = simplexml_load_file($root . '/' . $feedPath);
    if ($feed === false || !isset($feed->update)) {
        $fail("Invalid update feed {$feedPath}.");
    }
    $update = $feed->update;
    if (trim((string) $update->version) !== '2.2.0') {
        $fail("Update feed {$feedPath} must advertise 2.2.0.");
    }
    if (trim((string) $update->element) !== 'pkg_core') {
        $fail("Update feed {$feedPath} must resolve to canonical pkg_core.");
    }
    if (trim((string) $update->targetplatform['version']) !== '6\\.1\\.3') {
        $fail("Update feed {$feedPath} must target Joomla 6.1.3 exactly.");
    }
    if (trim((string) $update->php_minimum) !== '8.3.0') {
        $fail("Update feed {$feedPath} must require PHP 8.3.0+.");
    }
    if (!str_contains((string) $update->downloads->downloadurl, '/v2.2.0/pkg_core_2.2.0.zip')) {
        $fail("Update feed {$feedPath} must download canonical 2.2.0 package.");
    }
}

echo "PASS: Core 2.2 release readiness metadata.\n";
