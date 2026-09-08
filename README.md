# Core by xdecaro

**Core by xdecaro** is the shared technical foundation for the xdecaro Joomla ecosystem.

Version `1.1.0` adds the first opt-in shared design system and Web Asset Manager service while keeping Core domain-neutral and backward compatible. Product business logic remains in Forms, Courses, Competitions, Documents, Membership, Events, Editor and future extensions.

## Package

Core is distributed as `pkg_xdecarocore` and contains:

- `lib_xdecarocore` — shared PHP contracts and services;
- `plg_system_xdecarocore` — lightweight Joomla system integration point and shared media assets.

The library installs under `libraries/xdecaro/core` and uses the namespace `Xdecaro\Core`.

## Installation and updates

Install the versioned package ZIP directly through Joomla:

`pkg_xdecarocore_1.1.0.zip`

The package registers the official **Core by xdecaro** Joomla update server:

`https://raw.githubusercontent.com/xdecaro/core/main/updates/pkg_xdecarocore.xml`

Updates are distributed through GitHub Releases and verified with SHA-256. The update feed targets Joomla 4, 5 and 6 and Core 1.1.0 requires PHP 7.4 or newer. Joomla itself may impose a higher PHP requirement for the installed Joomla major.

## Shared Web Asset Manager API

Core 1.1.0 exposes `Xdecaro\Core\Asset\AssetService`.

Public asset identifiers:

- `xdecaro.core` — scoped design tokens/foundation;
- `xdecaro.components` — shared buttons, badges, cards, toolbar, fields, tables, empty/loading states and modal shell; it depends on `xdecaro.core`.

Assets are never injected globally. A consuming view explicitly opts in:

```php
use Xdecaro\Core\Asset\AssetService;

$webAssets = $this->getDocument()->getWebAssetManager();
$coreAssets = new AssetService();

if ($coreAssets->useComponents($webAssets)) {
    // Wrap only the UI that should inherit Core styles in .xdecaro-scope.
}
```

Shared CSS is scoped under `.xdecaro-scope`, uses `.xdecaro-*` classes and `--xdecaro-*` custom properties, and supports Joomla-oriented light/dark tokens plus an explicit `data-xdecaro-theme` override.

See `docs/asset-service.md` for usage and migration rules.

## Cross-product integration contracts

The storage-agnostic integration API remains unchanged:

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

Core does **not** persist these relationships. Each product keeps ownership of its own data. Persistence belongs in a product or a future shared service only after multiple real consumers prove a common storage requirement.

See `docs/integration-contracts.md` for the contract rules.

## Naming

The public product name is **Core by xdecaro**. Technical identifiers remain stable for backward compatibility, including `pkg_xdecarocore`, `lib_xdecarocore`, `plg_system_xdecarocore`, repository `xdecaro/core` and namespace `Xdecaro\Core`.

## Build integrity

`VERSION` is the release source of truth. The build validates library, plugin, package and Web Asset registry versions against it.

`bash build/build.sh` performs PHP/XML/JSON validation, integration and AssetService smoke tests, builds deterministic ZIP archives, verifies package/media contents and writes `dist/SHA256SUMS.txt`.

GitHub Actions runs the build on PHP 7.4 and PHP 8.3 and verifies that two consecutive builds produce identical SHA-256 hashes.

## Compatibility goals

Target Joomla 4, 5 and 6 where technically possible. Avoid product-specific assumptions and unnecessary runtime overhead.

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
