<?php
/**
 * Dependency-free smoke test for Core capability and event contracts.
 */

define('_JEXEC', 1);

require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/EntityReference.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/Capability.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/IntegrationEvent.php';

use Xdecaro\Core\Integration\Capability;
use Xdecaro\Core\Integration\EntityReference;
use Xdecaro\Core\Integration\IntegrationEvent;

$capability = new Capability('com_decaronotifications', 'notifications.publish', '1');

if ($capability->key() !== 'com_decaronotifications:notifications.publish@1') {
    throw new \RuntimeException('Capability key serialization failed.');
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
    new Capability('com_decaronotifications', 'Notifications Publish', '1');
} catch (\InvalidArgumentException $exception) {
    $invalidRejected = true;
}

if (!$invalidRejected) {
    throw new \RuntimeException('Invalid capability identifiers must be rejected.');
}

echo "Xdecaro Core integration capability/event tests passed.\n";
