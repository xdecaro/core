<?php

declare(strict_types=1);

$cssPath = __DIR__ . '/../src/plg_system_xdecarocore/media/css/core.css';
$css = (string) file_get_contents($cssPath);

$required = [
    'body.admin #wrapper:has(.xdecaro-scope) > .container-main',
    'width: auto;',
    'min-width: 0;',
    'flex: 1 1 0;',
];

foreach ($required as $needle) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, "Missing scoped Atum width fix: {$needle}\n");
        exit(1);
    }
}

if (str_contains($css, "body.admin .container-main {")) {
    fwrite(STDERR, "Atum width fix must remain scoped to pages containing .xdecaro-scope.\n");
    exit(1);
}

echo "Core by xdecaro Atum layout regression test passed.\n";
