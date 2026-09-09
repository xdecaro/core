# Core by xdecaro

**Core by xdecaro** is the shared technical foundation for the xdecaro Joomla ecosystem.

Version `1.5.0` adds a central Joomla administrator dashboard while keeping the public `xdecaro\Core` contracts backward compatible and product business logic outside Core.

## Package

Core is distributed as `pkg_xdecarocore` and contains:

- `lib_xdecarocore` — canonical shared PHP contracts and services under `xdecaro\Core`;
- `lib_xdecarocorelegacy` — temporary autoload compatibility for the deprecated `Xdecaro\Core` prefix;
- `com_xdecarocore` — the administrator component displayed in Joomla as **xdecaro**;
- `plg_system_xdecarocore` — lightweight Joomla system integration point and shared media assets.

New code must use `xdecaro\Core`. The legacy namespace is compatibility-only, receives no separate API, and may be removed in a future major release after all supported consumers have migrated.

## Installation and updates

Install the versioned package ZIP directly through Joomla:

`pkg_xdecarocore_1.5.0.zip`

Core `1.5.0` can be installed over Core `1.4.0`; the package uses Joomla's normal upgrade path and adds the administrator component without removing the existing libraries or system plugin.

The package registers the official update feed at `updates/pkg_xdecarocore.xml`. Releases are deterministic and verified with SHA-256. The feed targets Joomla 4, 5 and 6 where technically possible and declares PHP 7.4 or newer; the installed Joomla major may require a newer PHP version.

## xdecaro administrator dashboard

After Core `1.5.0` is installed, Joomla exposes **Components → xdecaro**.

The dashboard provides:

- **Dashboard** — ecosystem summary and immediate diagnostics;
- **Components** — all known xdecaro products, including installed, not installed, prerelease, development and planned states;
- **All extensions** — the technical Joomla extensions detected for the ecosystem, including packages, components, plugins, libraries and modules;
- **Updates** — installed-versus-available version comparison using the bundled catalog and Joomla's local update cache;
- **Diagnostics** — Core presence, partial package detection and disabled package plugins/modules;
- **Information** — Core/Joomla/PHP runtime information and development status.

Installed state is read from Joomla `#__extensions`. Package contents are grouped using Joomla's `package_id`, so the dashboard reflects the real extensions installed on the site instead of assuming a fixed child list.

The product catalog is a release-time baseline for products that are not installed yet. For installed extensions, a newer version already discovered by Joomla's updater takes precedence over the bundled catalog value. The dashboard deliberately does not perform remote network requests on every administrator page load.

### Licensing during development

Core `1.5.0` does **not** enforce commercial licensing. The Information screen only reports that licensing is deferred while the suite is still under development. Installed components, updates and features are not blocked by a license check in this release.

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

Assets are opt-in and never injected globally. Shared CSS remains scoped under `.xdecaro-scope`, with `.xdecaro-*` classes and `--xdecaro-*` CSS custom properties.

## Cross-product integration contracts

Canonical contracts:

- `xdecaro\Core\Integration\EntityReference`;
- `xdecaro\Core\Integration\RelationReference`;
- `xdecaro\Core\Integration\Capability`;
- `xdecaro\Core\Integration\CapabilityRegistry`;
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
- administrator component `com_xdecarocore`;
- canonical library `lib_xdecarocore`;
- temporary compatibility library `lib_xdecarocorelegacy`;
- system plugin `plg_system_xdecarocore`;
- repository `xdecaro/core`;
- canonical PHP namespace `xdecaro\Core`.

## Build integrity

`VERSION` is the release source of truth. `bash build/build.sh` validates PHP, XML, JSON, canonical/legacy namespace mappings, dashboard catalog metadata, integration contracts, Web Asset Manager assets, deterministic ZIP output and package contents, then writes `dist/SHA256SUMS.txt`.

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
