# Core by xdecaro

**Core by xdecaro** is the shared technical foundation for the xdecaro Joomla ecosystem.

Version `1.0.1` remains intentionally small. Core provides reusable, domain-neutral infrastructure and stable integration contracts. Product business logic remains in Forms, Courses, Competitions, Documents, Membership, Events, Editor and future extensions.

## Package

Core is distributed as `pkg_xdecarocore` and contains:

- `lib_xdecarocore` — shared PHP contracts and services;
- `plg_system_xdecarocore` — lightweight Joomla system integration point.

The library installs under `libraries/xdecaro/core` and uses the namespace `Xdecaro\Core`.

## Installation and updates

Install the versioned package ZIP directly through Joomla:

`pkg_xdecarocore_1.0.1.zip`

The package registers the official **Core by xdecaro** Joomla update server:

`https://raw.githubusercontent.com/xdecaro/core/main/updates/pkg_xdecarocore.xml`

Updates are distributed through GitHub Releases and verified with SHA-256 before Joomla installs them. The update feed targets Joomla 4, 5 and 6 and Core 1.0.1 requires PHP 7.4 or newer. Joomla itself may impose a higher PHP requirement for the installed Joomla major.

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

Core does **not** persist these relationships in 1.0.x. Each product keeps ownership of its own data. Persistence belongs in a product or a future shared service only after multiple real consumers prove a common storage requirement.

See `docs/integration-contracts.md` for the contract rules.

## Naming

The public product name is **Core by xdecaro**. Technical identifiers remain stable for backward compatibility, including `pkg_xdecarocore`, `lib_xdecarocore`, `plg_system_xdecarocore`, repository `xdecaro/core` and namespace `Xdecaro\Core`.

## Build integrity

`VERSION` is the release source of truth. The build validates the library, plugin and package manifest versions against it.

`bash build/build.sh` performs PHP/XML validation, smoke tests, builds deterministic ZIP archives, verifies package contents and writes `dist/SHA256SUMS.txt`.

GitHub Actions runs the build on PHP 7.4 and PHP 8.3 and verifies that two consecutive builds produce identical SHA-256 hashes.

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
