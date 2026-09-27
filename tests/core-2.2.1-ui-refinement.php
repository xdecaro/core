<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$componentsCssPath = $root . '/src/plg_system_xdecarocore/media/css/components.css';
$componentsCss = (string) file_get_contents($componentsCssPath);

$requiredSelectors = [
    '.xdecaro-suite__page-header',
    '.xdecaro-suite__eyebrow',
    '.xdecaro-suite__title',
    '.xdecaro-suite__description',
    '.xdecaro-suite__metric.is-primary',
    '.xdecaro-suite__metric.is-success',
    '.xdecaro-suite__metric.is-warning',
    '.xdecaro-suite__metric.is-danger',
    '.xdecaro-suite__metric.is-neutral',
];

foreach ($requiredSelectors as $selector) {
    if (!str_contains($componentsCss, $selector)) {
        throw new RuntimeException('Missing Core 2.2.1 shared UI selector: ' . $selector);
    }
}

$requiredFragments = [
    'border-inline-start:',
    'var(--xdecaro-color-primary)',
    'var(--xdecaro-color-success)',
    'var(--xdecaro-color-warning)',
    'var(--xdecaro-color-danger)',
    'var(--xdecaro-color-muted)',
    'font-size: clamp(1.9rem',
    'font-size: clamp(1rem',
];

foreach ($requiredFragments as $fragment) {
    if (!str_contains($componentsCss, $fragment)) {
        throw new RuntimeException('Missing Core 2.2.1 shared UI fragment: ' . $fragment);
    }
}

if (preg_match('/\.xdecaro-suite\s+\.xdecaro-suite__metric\.is-(primary|success|warning|danger|neutral)[^{]*\{[^}]*background\s*:/s', $componentsCss) === 1) {
    throw new RuntimeException('Metric semantic variants must use a border accent without painting the whole card background.');
}

echo "Core 2.2.1 page header and metric variant contract passed.\n";
