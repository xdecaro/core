# Capability Registry

`xdecaro\Core\Integration\CapabilityRegistry` is the runtime, in-memory registry for optional xdecaro product capabilities introduced in Core 1.4.0.

## Boundary

The registry stores only `Capability` value objects for the current runtime. It does not persist anything, boot optional products, dispatch events, read product tables or own product logic.

Products remain responsible for declaring their capabilities. Missing optional products are a normal condition.

## Basic usage

```php
use xdecaro\Core\Integration\CapabilityRegistry;

$registry = new CapabilityRegistry();
$registry->registerMany($productCoreIntegration->getCapabilities());

if ($registry->supports('com_xdecaronotifications', 'notifications.publish', '1')) {
    // Call Notifications through its public component service.
}
```

`supports()` accepts a minimum capability version. A provider declaring version `1.2` satisfies a request for `1` or `1.1`, but not `2`.

## Notifications

Notifications 1.0+ owns notification persistence, recipient state, preferences, queueing and delivery. Its declared v1 capability family is:

- `notifications.publish`
- `notifications.query`
- `notifications.state`
- `notifications.unread_count`
- `notifications.preferences`
- `notifications.delivery_status`
- `notifications.delivery_channels`

Core does not hardcode or implement these operations. The names are owned by Notifications and are only registered when Notifications is available.

## Tasks

Initial Tasks capabilities include:

- `tasks.create`
- `tasks.assign`
- `tasks.complete`
- `tasks.query`

## Analytics

Initial Analytics capabilities include:

- `analytics.metrics`
- `analytics.datasets`
- `analytics.reports`

## Security

A capability indicates API availability, not authorization. The provider must enforce Joomla ACL, CSRF protection where applicable, input validation and record-level authorization on every operation.

An `EntityReference` identifies a source record but never grants access to it.

## Dependency rule

Allowed: `Product -> Core`.

Forbidden: `Core -> Product`.

Core must stay installable without Notifications, Tasks, Analytics or any other optional xdecaro component.
