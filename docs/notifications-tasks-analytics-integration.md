# Notifications, Tasks and Analytics integration

## Purpose

Notifications, Tasks and Analytics are independent xdecaro products. Core provides only domain-neutral contracts so they can integrate with other products without direct table access or circular dependencies.

Dependency direction remains:

`Notifications / Tasks / Analytics -> Core by xdecaro`

Core must never depend on those products.

## Core contracts

Canonical Core 1.3+ classes are:

- `xdecaro\Core\Integration\EntityReference` for stable references to records owned by another component;
- `xdecaro\Core\Integration\RelationReference` for typed cross-product relations;
- `xdecaro\Core\Integration\Capability` for stable public capability identifiers;
- `xdecaro\Core\Integration\IntegrationEvent` for a neutral event envelope.

The former uppercase vendor prefix is compatibility-only for already-published consumers. New integration code uses lowercase `xdecaro`.

Core does not persist capabilities, events, notifications, tasks, metrics or reports. It does not queue or dispatch product events.

## Component identities

- Notifications: `com_xdecaronotifications`;
- Tasks: `com_xdecarotasks`;
- Analytics: `com_xdecaroanalytics`.

These products own their own data and workflows. Their repositories must not query another product's private tables as an integration shortcut.

## Capability naming

Capability names are product-owned and versioned by the provider. Recommended initial identifiers:

### Notifications

- `notifications.publish`
- `notifications.preferences`
- `notifications.delivery_status`

### Tasks

- `tasks.create`
- `tasks.assign`
- `tasks.complete`
- `tasks.query`

### Analytics

- `analytics.metrics`
- `analytics.datasets`
- `analytics.reports`

These names are conventions for the owning components, not hardcoded Core logic.

## Event ownership

Each source component owns its event names and payload schema. Examples may include:

- `documents.document.expiring`
- `finance.payment.overdue`
- `competitions.match.updated`
- `inventory.stock.low`

Notifications may consume these events to produce alerts. Tasks may consume them to create actionable work. Analytics may consume them or query public providers to build metrics.

Core must not define product-specific event names.

## Notifications boundary

Notifications owns notification persistence, recipients and preferences, read/unread state, channels and delivery attempts, retries/errors, history, templates and priorities.

A notification may retain the original `EntityReference` so the user can navigate back to the source record without copying its business data.

## Tasks boundary

Tasks owns task persistence, assignees, status, priority, due dates, checklist, comments, completion history and links to source records.

The source record remains owned by its original component. A task should reference it using `EntityReference` rather than a cross-component foreign key.

## Analytics boundary

Analytics owns metric definitions, datasets, aggregations, dashboards, reports, analytics-owned snapshots/caches and export/report scheduling semantics.

Analytics must prefer public providers/events over reading private component tables. It must not become the source of truth for operational data.

## Security

A reference never grants permission. Every provider must enforce its own ACL before exposing data or accepting writes.

Event payloads should contain the minimum data required for the integration. Sensitive or authorization-dependent data should be resolved from the owning product through an authorized provider rather than copied into generic payloads.

## Optional integration

Every integration remains optional:

- a source product must continue to work if Notifications is missing;
- a source product must continue to work if Tasks is missing;
- a source product must continue to work if Analytics is missing.

Products should detect availability through documented capability/provider mechanisms and fail gracefully when an optional integration is unavailable.

## Example

```php
use xdecaro\Core\Integration\EntityReference;
use xdecaro\Core\Integration\IntegrationEvent;

$document = new EntityReference('com_decarodocuments', 'document', 42);

$event = new IntegrationEvent(
    'documents.document.expiring',
    ['daysRemaining' => 7],
    $document,
    '1',
    '2026-09-08T20:00:00+02:00'
);
```

The source component may dispatch or hand this envelope to an installed integration adapter. Core itself performs no dispatch and no persistence.
