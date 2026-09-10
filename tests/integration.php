<?php
/**
 * Dependency-free smoke test for Core integration contracts.
 */

define('_JEXEC', 1);

require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/EntityReference.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/Capability.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/CapabilityRegistry.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/IntegrationEvent.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Location/LocationResult.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Location/LocationProviderInterface.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Location/WorldLocationService.php';

use xdecaro\Core\Integration\Capability;
use xdecaro\Core\Integration\CapabilityRegistry;
use xdecaro\Core\Integration\EntityReference;
use xdecaro\Core\Integration\IntegrationEvent;
use xdecaro\Core\Location\LocationProviderInterface;
use xdecaro\Core\Location\LocationResult;
use xdecaro\Core\Location\WorldLocationService;

$capability = new Capability('com_xdecaronotifications', 'notifications.publish', '1');
if ($capability->key() !== 'com_xdecaronotifications:notifications.publish@1') {
    throw new \RuntimeException('Capability key serialization failed.');
}

$registry = new CapabilityRegistry();
$registry->registerMany([
    $capability,
    new Capability('com_xdecaronotifications', 'notifications.query', '1'),
    new Capability('com_xdecarotasks', 'tasks.create', '1'),
    new Capability('com_xdecaroanalytics', 'analytics.metrics', '1.1'),
]);

if (!$registry->supports('com_xdecaronotifications', 'notifications.publish', '1')) {
    throw new \RuntimeException('CapabilityRegistry failed to resolve Notifications publish capability.');
}
if (!$registry->supports('com_xdecaroanalytics', 'analytics.metrics', '1')) {
    throw new \RuntimeException('CapabilityRegistry minimum-version matching failed.');
}
if ($registry->supports('com_xdecaroanalytics', 'analytics.metrics', '2')) {
    throw new \RuntimeException('CapabilityRegistry accepted an unsupported minimum version.');
}
if (count($registry->forComponent('com_xdecaronotifications')) !== 2) {
    throw new \RuntimeException('CapabilityRegistry component filtering failed.');
}

$registryRoundTrip = CapabilityRegistry::fromArray($registry->toArray());
if ($registryRoundTrip->count() !== 4 || !$registryRoundTrip->supports('com_xdecarotasks', 'tasks.create', '1')) {
    throw new \RuntimeException('CapabilityRegistry serialization round-trip failed.');
}

$source = new EntityReference('com_decarodocuments', 'document', 42);
$event = new IntegrationEvent(
    'documents.document.expiring',
    ['daysRemaining' => 7],
    $source,
    '1',
    '2026-09-08T20:00:00+02:00'
);

$roundTrip = IntegrationEvent::fromArray($event->toArray());
if ($roundTrip->getSource() === null || $roundTrip->getSource()->key() !== $source->key()) {
    throw new \RuntimeException('IntegrationEvent source round-trip failed.');
}
if (($roundTrip->getPayload()['daysRemaining'] ?? null) !== 7) {
    throw new \RuntimeException('IntegrationEvent payload round-trip failed.');
}

$invalidRejected = false;
try {
    new Capability('com_xdecaronotifications', 'Notifications Publish', '1');
} catch (\InvalidArgumentException $exception) {
    $invalidRejected = true;
}
if (!$invalidRejected) {
    throw new \RuntimeException('Invalid capability identifiers must be rejected.');
}

$locationProvider = new class implements LocationProviderInterface {
    public function searchCities(string $query, ?string $countryCode = null, string $language = 'en', int $limit = 20): array
    {
        return [
            new LocationResult('3169070', 'Roma', 'IT', 'Italia', 'Lazio', 'Roma', 41.89193, 12.51133, 'Europe/Rome', 'PPLC', 2318895),
            new LocationResult('3169070', 'Roma', 'IT', 'Italia', 'Lazio', 'Roma'),
        ];
    }
};

$locations = (new WorldLocationService($locationProvider))->searchCities('Rom', 'IT', 'it', 20);
if (count($locations) !== 1) {
    throw new \RuntimeException('WorldLocationService must collapse duplicate provider IDs.');
}
if (($locations[0]['id'] ?? null) !== '3169070' || ($locations[0]['country_code'] ?? null) !== 'IT') {
    throw new \RuntimeException('WorldLocationService returned an invalid public location contract.');
}
if (($locations[0]['label'] ?? null) !== 'Roma — Lazio — Italia') {
    throw new \RuntimeException('WorldLocationService generated an invalid display label.');
}

echo "xdecaro Core integration capability/event/location tests passed.\n";
