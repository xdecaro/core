# Xdecaro Core

Xdecaro Core is the shared technical foundation for the Xdecaro Joomla ecosystem.

Version `1.0.0` is intentionally small. Core provides reusable, domain-neutral infrastructure and stable integration contracts. Product business logic remains in Forms, Courses, Competitions, Documents, Membership, Events, Editor and future extensions.

## Initial package

Core is distributed as `pkg_xdecarocore` and contains:

- `lib_xdecarocore` — shared PHP contracts and services;
- `plg_system_xdecarocore` — lightweight Joomla system integration point.

The library installs under `libraries/xdecaro/core` and uses the namespace `Xdecaro\Core`.

## First public integration contract

The first shared contract is intentionally storage-agnostic:

- `Xdecaro\Core\Integration\EntityReference` identifies an entity owned by a Joomla component;
- `Xdecaro\Core\Integration\RelationReference` describes a typed relationship between two entity references.

Example:

```php
use Xdecaro\Core\Integration\EntityReference;
use Xdecaro\Core\Integration\RelationReference;

$member = new EntityReference('com_decaromembership', 'member', 125);
$enrollment = new EntityReference('com_decarocourses', 'enrollment', 487);
$relation = new RelationReference($member, $enrollment, 'participant');
```

Core does **not** persist these relationships in 1.0.0. Each product keeps ownership of its own data. Persistence belongs in a product or a future shared service only after multiple real consumers prove a common storage requirement.

See `docs/integration-contracts.md` for the contract rules.

## Compatibility goals

Target Joomla 4, 5 and 6 where technically possible. Avoid product-specific assumptions and unnecessary runtime overhead.

## Development principles

- modern Joomla APIs;
- server-side ACL and CSRF where applicable;
- bound database queries;
- Web Asset Manager for shared assets;
- Semantic Versioning;
- backward-compatible public APIs;
- clean install/update paths;
- no destructive database migrations;
- responsive/light/dark shared UI only when genuinely common.
