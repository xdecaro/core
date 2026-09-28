<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$script = (string) file_get_contents($root . '/src/plg_system_xdecarocore/media/js/filterbar.js');
$assets = (string) file_get_contents($root . '/src/plg_system_xdecarocore/media/joomla.asset.json');

foreach ([
    "matchMedia('(prefers-reduced-motion: reduce)')",
    'panel.animate(',
    'animation.finished',
    'xdecaroFilterbarReady',
] as $marker) {
    if (!str_contains($script, $marker)) {
        throw new RuntimeException('Shared filterbar animation marker missing: ' . $marker);
    }
}

if (!str_contains($assets, '"version": "2.2.10"')) {
    throw new RuntimeException('Core Web Asset registry must be versioned 2.2.10 for the animated filterbar candidate.');
}

echo "Core 2.2.10 filterbar animation contract passed.\n";
