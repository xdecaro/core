<?php
/**
 * Dependency-free smoke test for Core capability and event contracts.
 */

define('_JEXEC', 1);

require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/EntityReference.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/Capability.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/CapabilityRegistry.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/IntegrationEvent.php';

use xdecaro\Core\Integration\Capability;
use xdecaro\Core\Integration\CapabilityRegistry;
use xdecaro\Core\Integration\EntityReference;
use xdecaro\Core\Integration\IntegrationEvent;

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

echo "xdecaro Core integration capability/event/registry tests passed.\n";
