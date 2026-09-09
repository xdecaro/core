<?php
/**
 * Dependency-free smoke test for the Core ecosystem dashboard catalog and menu contract.
 */

namespace Joomla\Database {
    interface DatabaseInterface {}
}

namespace {
    define('_JEXEC', 1);

    require_once __DIR__ . '/../src/com_xdecarocore/admin/src/Service/EcosystemService.php';

    $reflection = new \ReflectionClass(\xdecaro\Component\Core\Administrator\Service\EcosystemService::class);
    $constant = $reflection->getReflectionConstant('CATALOG');
    if ($constant === false) {
        throw new \RuntimeException('Dashboard catalog constant is missing.');
    }

    $catalog = $constant->getValue();
    if (!is_array($catalog) || count($catalog) < 15) {
        throw new \RuntimeException('Dashboard catalog is unexpectedly small.');
    }

    $required = [
        'core' => ['pkg_xdecarocore', '1.5.1'],
        'forms' => ['pkg_decaroforms', '1.7.0'],
        'courses' => ['pkg_decarocourses', '1.5.0'],
        'competitions' => ['pkg_xdecarocompetitions', '1.3.0'],
        'documents' => ['pkg_decarodocuments', '1.3.0'],
        'membership' => ['pkg_decaromembership', '1.4.0'],
        'finance' => ['pkg_decarofinance', '1.3.0'],
        'protocol' => ['pkg_decaroprotocol', '1.4.0'],
    ];

    foreach ($required as $key => $expected) {
        if (!isset($catalog[$key])) {
            throw new \RuntimeException('Missing dashboard product: ' . $key);
        }
        if (($catalog[$key]['package'] ?? '') !== $expected[0] || ($catalog[$key]['version'] ?? '') !== $expected[1]) {
            throw new \RuntimeException('Dashboard catalog mismatch for ' . $key);
        }
    }

    if (($catalog['editor']['channel'] ?? '') !== 'prerelease') {
        throw new \RuntimeException('Editor must remain marked as prerelease.');
    }
    if (($catalog['communications']['channel'] ?? '') !== 'planned') {
        throw new \RuntimeException('Communications must remain marked as planned.');
    }

    $manifest = simplexml_load_file(__DIR__ . '/../src/com_xdecarocore/xdecarocore.xml');
    if ($manifest === false) {
        throw new \RuntimeException('Core administrator component manifest is invalid.');
    }

    $expectedLinks = [
        'option=com_xdecarocore&view=dashboard&layout=products',
        'option=com_xdecarocore&view=dashboard&layout=extensions',
        'option=com_xdecarocore&view=dashboard&layout=updates',
        'option=com_xdecarocore&view=dashboard&layout=diagnostics',
        'option=com_xdecarocore&view=dashboard&layout=information',
    ];
    $actualLinks = [];

    foreach ($manifest->administration->submenu->menu as $menu) {
        $link = trim((string) $menu['link']);
        if ($link !== '') {
            $actualLinks[] = $link;
        }
    }

    if ($actualLinks !== $expectedLinks) {
        throw new \RuntimeException('Dashboard submenu links are not normalized for Joomla administrator routing.');
    }

    foreach ($actualLinks as $link) {
        if (strpos($link, 'index.php?') === 0) {
            throw new \RuntimeException('Dashboard submenu link must not contain an index.php? prefix in the manifest.');
        }
    }

    echo "xdecaro Core dashboard catalog and submenu tests passed.\n";
}
