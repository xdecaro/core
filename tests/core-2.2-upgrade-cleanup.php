<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$script = (string) file_get_contents($root . '/package/pkg_core/script.php');

$required = [
    'removeRetiredResponsiveStylesheet',
    "JPATH_ROOT . '/media/com_xdecarocore/css/responsive.css'",
    'if (!is_file($path))',
    '@unlink($path)',
];

foreach ($required as $needle) {
    if (!str_contains($script, $needle)) {
        fwrite(STDERR, "Missing Core 2.2 retired responsive stylesheet cleanup contract: {$needle}\n");
        exit(1);
    }
}

if (!preg_match('/postflight\s*\([^)]*\).*?removeRetiredResponsiveStylesheet\s*\(/s', $script)) {
    fwrite(STDERR, "Core 2.2 postflight must invoke the retired responsive stylesheet cleanup.\n");
    exit(1);
}

echo "Core 2.2 upgrade cleanup contract passed.\n";
