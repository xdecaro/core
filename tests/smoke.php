<?php
/**
 * Minimal dependency-free smoke test for Core public integration contracts.
 */

define('_JEXEC', 1);

require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/EntityReference.php';
require_once __DIR__ . '/../src/lib_xdecarocore/src/Integration/RelationReference.php';

use xdecaro\Core\Integration\EntityReference;
use xdecaro\Core\Integration\RelationReference;

$member = new EntityReference('com_decaromembership', 'member', 125);
$enrollment = new EntityReference('com_decarocourses', 'enrollment', '487');
$relation = new RelationReference($member, $enrollment, 'participant');

if ($member->key() !== 'com_decaromembership:member:125') {
    throw new \RuntimeException('EntityReference key serialization failed.');
}

if ($relation->toArray() !== [
    'source' => ['component' => 'com_decaromembership', 'entity' => 'member', 'id' => '125'],
    'target' => ['component' => 'com_decarocourses', 'entity' => 'enrollment', 'id' => '487'],
    'type' => 'participant',
]) {
    throw new \RuntimeException('RelationReference serialization failed.');
}

$roundTrip = RelationReference::fromArray($relation->toArray());
if ($roundTrip->getSource()->key() !== $member->key() || $roundTrip->getTarget()->key() !== $enrollment->key()) {
    throw new \RuntimeException('RelationReference round-trip failed.');
}

$invalidRejected = false;
try {
    new EntityReference('not_a_component', 'member', 1);
} catch (\InvalidArgumentException $exception) {
    $invalidRejected = true;
}
if (!$invalidRejected) {
    throw new \RuntimeException('Invalid component identifiers must be rejected.');
}

$invalidRejected = false;
try {
    new RelationReference($member, $enrollment, 'Not Valid');
} catch (\InvalidArgumentException $exception) {
    $invalidRejected = true;
}
if (!$invalidRejected) {
    throw new \RuntimeException('Invalid relation types must be rejected.');
}

echo "xdecaro Core smoke tests passed.\n";
