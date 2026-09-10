<?php

define('_JEXEC', 1);

$root = dirname(__DIR__);
require_once $root . '/src/lib_xdecarocore/src/Location/LocationResult.php';
require_once $root . '/src/lib_xdecarocore/src/Location/LocationProviderInterface.php';
require_once $root . '/src/lib_xdecarocore/src/Location/WorldLocationService.php';

use xdecaro\Core\Location\LocationProviderInterface;
use xdecaro\Core\Location\LocationResult;
use xdecaro\Core\Location\WorldLocationService;

$provider = new class implements LocationProviderInterface {
    public function searchCities(string $query, ?string $countryCode = null, string $language = 'en', int $limit = 20): array
    {
        return [
            new LocationResult('3169070', 'Roma', 'IT', 'Italia', 'Lazio', 'Roma', 41.89193, 12.51133, 'Europe/Rome', 'PPLC', 2318895),
            new LocationResult('3169070', 'Roma', 'IT', 'Italia', 'Lazio', 'Roma'),
        ];
    }
};

$service = new WorldLocationService($provider);
$items = $service->searchCities('Rom', 'IT', 'it', 20);

if (count($items) !== 1) {
    fwrite(STDERR, "Expected duplicate location IDs to be collapsed.\n");
    exit(1);
}

$item = $items[0];
if (($item['id'] ?? null) !== '3169070' || ($item['country_code'] ?? null) !== 'IT') {
    fwrite(STDERR, "Unexpected normalized location result.\n");
    exit(1);
}

if (($item['label'] ?? null) !== 'Roma — Lazio — Italia') {
    fwrite(STDERR, "Unexpected location label.\n");
    exit(1);
}

echo "World location contract OK\n";
