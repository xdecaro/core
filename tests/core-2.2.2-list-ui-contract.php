<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$admin = (string) file_get_contents($root . '/src/plg_system_xdecarocore/media/css/admin.css');
$components = (string) file_get_contents($root . '/src/plg_system_xdecarocore/media/css/components.css');

$requiredAdmin = [
    'min-height: 5.5rem',
    'padding: 1.125rem 1.25rem',
    'flex: 0 1 13rem',
    '.xdecaro-suite__responsive-table tbody tr:hover',
    '@container xdecaro-suite (max-width: 38rem)',
    'grid-template-columns: repeat(2, minmax(0, 1fr))',
];

foreach ($requiredAdmin as $fragment) {
    if (!str_contains($admin, $fragment)) {
        throw new RuntimeException('Core 2.2.2 compact list UI missing admin fragment: ' . $fragment);
    }
}

$requiredComponents = [
    'font-size: clamp(1.65rem, 2.4vw, 2.125rem)',
    'font-size: 0.9375rem',
    '.xdecaro-suite .xdecaro-suite__metric.is-selected',
    'box-shadow: inset 0 0 0 1px',
];

foreach ($requiredComponents as $fragment) {
    if (!str_contains($components, $fragment)) {
        throw new RuntimeException('Core 2.2.2 compact list UI missing component fragment: ' . $fragment);
    }
}

foreach (['.dc-app', '.dc-page-head', '.dc-stat', '.dc-table'] as $coursesPrivateSelector) {
    if (str_contains($admin, $coursesPrivateSelector) || str_contains($components, $coursesPrivateSelector)) {
        throw new RuntimeException('Courses-private selector leaked into Core shared UI: ' . $coursesPrivateSelector);
    }
}

echo "Core 2.2.2 compact list UI contract passed.\n";
