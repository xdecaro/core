<?php
/**
 * Verifies that an autoloader registered for the deprecated Xdecaro\Core
 * prefix can load the canonical lowercase class declarations.
 */

define('_JEXEC', 1);

$root = __DIR__ . '/../src/lib_xdecarocore/src/';

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'Xdecaro\\Core\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $root . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

$legacyClass = 'Xdecaro\\Core\\Integration\\EntityReference';
if (!class_exists($legacyClass)) {
    throw new RuntimeException('Deprecated Core namespace cannot autoload canonical sources.');
}

$reference = new $legacyClass('com_xdecarocompetitions', 'competition', 7);

if ($reference->key() !== 'com_xdecarocompetitions:competition:7') {
    throw new RuntimeException('Legacy Core namespace compatibility produced an invalid reference.');
}

if (!$reference instanceof \xdecaro\Core\Integration\EntityReference) {
    throw new RuntimeException('Legacy Core namespace must resolve to the canonical class declaration.');
}

echo "Core namespace compatibility tests passed.\n";
