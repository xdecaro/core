# Core by xdecaro

**Core by xdecaro** is the shared technical foundation for the xdecaro Joomla ecosystem.

Version `1.3.0` makes the lowercase PHP vendor namespace `xdecaro\Core` canonical. To protect already-published consumers, the package temporarily ships a compatibility library that also registers the deprecated `Xdecaro\Core` prefix. Product business logic remains outside Core.

## Package

Core is distributed as `pkg_xdecarocore` and contains:

- `lib_xdecarocore` — canonical shared PHP contracts and services under `xdecaro\Core`;
- `lib_xdecarocorelegacy` — temporary autoload compatibility for `Xdecaro\Core` only;
- `plg_system_xdecarocore` — lightweight Joomla system integration point and shared media assets.

New code must use `xdecaro\Core`. The legacy namespace is compatibility-only, receives no separate API, and may be removed in a future major release after all supported consumers have migrated.

## Installation and updates

Install the versioned package ZIP directly through Joomla:

`pkg_xdecarocore_1.3.0.zip`

The package registers the official update feed at `updates/pkg_xdecarocore.xml`. Releases are deterministic and verified with SHA-256. The feed targets Joomla 4, 5 and 6 where technically possible and declares PHP 7.4 or newer; the installed Joomla major may require a newer PHP version.

## Shared Web Asset Manager API

Canonical API:

```php
use xdecaro\Core\Asset\AssetService;

$webAssets = $this->getDocument()->getWebAssetManager();
$coreAssets = new AssetService();

if ($coreAssets->useComponents($webAssets)) {
    // Wrap only the UI that should inherit Core styles in .xdecaro-scope.
}
```

Public asset identifiers remain stable:

- `xdecaro.core` — scoped design tokens/foundation;
- `xdecaro.components` — shared UI primitives; depends on `xdecaro.core`.

Assets are opt-in and never injected globally. Shared CSS remains scoped under `.xdecaro-scope`, with `.xdecaro-*` classes and `--xdecaro-*` custom properties.

## Cross-product integration contracts

Canonical contracts:

- `xdecaro\Core\Integration\EntityReference`;
- `xdecaro\Core\Integration\RelationReference`;
- `xdecaro\Core\Integration\Capability`;
- `xdecaro\Core\Integration\IntegrationEvent`.

Example:

```php
use xdecaro\Core\Integration\EntityReference;
use xdecaro\Core\Integration\IntegrationEvent;

$document = new EntityReference('com_decarodocuments', 'document', 42);
$event = new IntegrationEvent(
    'documents.document.expiring',
    ['daysRemaining' => 7],
    $document,
    '1'
);
```

Core does not persist cross-product relations, events, notifications, tasks, metrics or reports. Each product owns its data, ACL and business rules. A reference never grants authorization.

## Namespace compatibility policy

`xdecaro\Core` is the only namespace to use in new code from 1.3.0 onward. `Xdecaro\Core` remains temporarily loadable through `lib_xdecarocorelegacy` so existing Forms, Courses, Documents, Membership, Events and other published consumers are not broken by a case-only namespace migration.

The compatibility library contains the same canonical source files at build time; it does not fork or duplicate the API implementation. Removal requires a future major release and a verified ecosystem migration.

## Naming

Public product name: **Core by xdecaro**.

Stable technical identifiers:

- package `pkg_xdecarocore`;
- canonical library `lib_xdecarocore`;
- temporary compatibility library `lib_xdecarocorelegacy`;
- system plugin `plg_system_xdecarocore`;
- repository `xdecaro/core`;
- canonical PHP namespace `xdecaro\Core`.

## Build integrity

`VERSION` is the release source of truth. `bash build/build.sh` validates PHP, XML, JSON, canonical/legacy namespace mappings, integration contracts, Web Asset Manager assets, deterministic ZIP output and package contents, then writes `dist/SHA256SUMS.txt`.

## Compatibility goals

Target Joomla 4, 5 and 6 where technically possible. Runtime compatibility must still be verified on real Joomla installations; repository CI/build success is not a substitute for runtime testing.

## Development principles

- modern Joomla APIs;
- server-side ACL and CSRF where applicable;
- bound database queries;
- Web Asset Manager for shared assets;
- Semantic Versioning;
- backward-compatible public APIs and asset identifiers;
- clean install/update paths;
- no destructive database migrations;
- responsive/light/dark shared UI only when genuinely common.
