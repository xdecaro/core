<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$servicePath = $root . '/src/com_xdecarocore/admin/src/Service/EcosystemService.php';
$source = (string) file_get_contents($servicePath);

$required = [
    'private function findProductPackage(array $definition, ?array $component, array $extensions): ?array',
    '$canonical = $definition[\'package\'] !== \'\' ? $this->findExtension($extensions, \'package\', (string) $definition[\'package\']) : null;',
    'if ($component === null || (int) $component[\'package_id\'] <= 0)',
    '$this->findExtensionById($extensions, \'package\', (int) $component[\'package_id\'])',
    'private function findExtensionById(array $extensions, string $type, int $extensionId): ?array',
    '$package = $this->findProductPackage($definition, $component, $extensions);',
    '$packageElements = array_values(array_unique(array_filter([',
    '(string) ($package[\'element\'] ?? \'\'),',
    '$members = $this->loadPackageManifestMembers((string) $linked[\'element\']);',
    '$member[\'type\'] === \'component\' && $member[\'id\'] === (string) $definition[\'component\']',
];

foreach ($required as $fragment) {
    if (!str_contains($source, $fragment)) {
        throw new RuntimeException('Package linkage fallback contract missing fragment: ' . $fragment);
    }
}

if (!str_contains($source, "'package' => (string) (\$package['element'] ?? \$definition['package']),")) {
    throw new RuntimeException('Product snapshot must expose the actually installed package element when a package_id fallback is used.');
}

if (!str_contains($source, "'catalog_package' => (string) \$definition['package'],")) {
    throw new RuntimeException('Product snapshot must retain the catalog package identity separately.');
}

if (str_contains($source, "'people' => ['name' => 'People', 'package' => 'pkg_people'")) {
    throw new RuntimeException('Do not hardcode the legacy People package as the catalog identity.');
}

if (str_contains($source, "'organizations' => ['name' => 'Organizations', 'package' => 'pkg_organizations'")) {
    throw new RuntimeException('Do not hardcode the legacy Organizations package as the catalog identity.');
}

echo "Core package linkage fallback contract passed.\n";
