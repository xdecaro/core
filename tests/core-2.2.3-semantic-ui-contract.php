<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$core = (string) file_get_contents($root . '/src/plg_system_xdecarocore/media/css/core.css');
$components = (string) file_get_contents($root . '/src/plg_system_xdecarocore/media/css/components.css');
$admin = (string) file_get_contents($root . '/src/plg_system_xdecarocore/media/css/admin.css');

$requiredCoreTokens = [
    '--xdecaro-color-info:',
    '--xdecaro-color-info-soft:',
    '--xdecaro-color-neutral:',
    '--xdecaro-color-neutral-soft:',
    '--xdecaro-color-attention:',
    '--xdecaro-color-attention-soft:',
    '--xdecaro-color-development:',
    '--xdecaro-color-development-soft:',
    '--xdecaro-color-action:',
    '--xdecaro-color-action-soft:',
];

foreach ($requiredCoreTokens as $fragment) {
    if (!str_contains($core, $fragment)) {
        throw new RuntimeException('Core 2.2.3 semantic token missing: ' . $fragment);
    }
}

$requiredBadges = [
    '.xdecaro-badge--success',
    '.xdecaro-badge--warning',
    '.xdecaro-badge--danger',
    '.xdecaro-badge--info',
    '.xdecaro-badge--neutral',
    '.xdecaro-badge--attention',
    '.xdecaro-badge--primary',
    '.xdecaro-badge--development',
    '.xdecaro-badge--action',
    'border: 1px solid transparent',
    'text-decoration: none',
];

foreach ($requiredBadges as $fragment) {
    if (!str_contains($components, $fragment)) {
        throw new RuntimeException('Core 2.2.3 badge contract missing: ' . $fragment);
    }
}

$requiredButtons = [
    '.xdecaro-button--primary',
    '.xdecaro-button--secondary',
    '.xdecaro-button--filter',
    '.xdecaro-button--success',
    '.xdecaro-button--danger',
    '.xdecaro-button--warning',
    '.xdecaro-button--action',
];

foreach ($requiredButtons as $fragment) {
    if (!str_contains($components, $fragment)) {
        throw new RuntimeException('Core 2.2.3 button contract missing: ' . $fragment);
    }
}

$requiredFilterbar = [
    '.xdecaro-filterbar__toggle',
    '.xdecaro-filterbar__advanced',
    '.xdecaro-filterbar__close',
    'grid-template-columns: minmax(0, 1fr) auto auto auto',
];

foreach ($requiredFilterbar as $fragment) {
    if (!str_contains($admin, $fragment)) {
        throw new RuntimeException('Core 2.2.3 filterbar contract missing: ' . $fragment);
    }
}

echo "Core 2.2.3 semantic UI contract passed.\n";
