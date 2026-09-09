# Core by xdecaro

**Core by xdecaro** is the shared technical foundation for the xdecaro Joomla ecosystem.

Version `1.5.6` makes the administrator dashboard responsive to the width actually available inside Joomla. Secondary views no longer rely only on the browser viewport: when the Joomla administrator sidebar stays open, Core uses container queries and narrow technical tables switch to readable record cards. Public `xdecaro\Core` contracts remain unchanged and product business logic stays outside Core.

## Package

Core is distributed as `pkg_xdecarocore` and contains:

- `lib_xdecarocore` — canonical shared PHP contracts and services under `xdecaro\Core`;
- `lib_xdecarocorelegacy` — temporary autoload compatibility for the deprecated `Xdecaro\Core` prefix;
- `com_xdecarocore` — the administrator component displayed in Joomla as **xdecaro**;
- `plg_system_xdecarocore` — lightweight Joomla system integration point and shared media assets.

New code must use `xdecaro\Core`. The legacy namespace is compatibility-only, receives no separate API, and may be removed in a future major release after all supported consumers have migrated.

## Installation and updates

Install the versioned package ZIP directly through Joomla:

`pkg_xdecarocore_1.5.6.zip`

Core `1.5.6` can be installed directly over an existing 1.5.x installation; the package uses Joomla's normal upgrade path without removing the existing libraries, administrator component, system plugin, data or configuration.

The package registers the official update feed at `updates/pkg_xdecarocore.xml`. Releases are deterministic and verified with SHA-256. The feed targets Joomla 4, 5 and 6 where technically possible and declares PHP 7.4 or newer; the installed Joomla major may require a newer PHP version.

## xdecaro administrator dashboard

After Core is installed, Joomla exposes **Components → xdecaro**.

The dashboard provides:

- **Dashboard** — ecosystem summary and immediate diagnostics;
- **Components** — all known xdecaro products, including installed, not installed, prerelease, development and planned states;
- **All extensions** — the technical Joomla extensions detected for the ecosystem, including packages, components, plugins, libraries and modules;
- **Updates** — installed-versus-available version comparison using the bundled catalog and Joomla's local update cache;
- **Diagnostics** — Core presence, partial package detection and disabled package plugins/modules;
- **Information** — Core/Joomla/PHP runtime information and development status.

All six administrator views use the same suite hierarchy: a blue **Suite** eyebrow, the page title and its description. Information uses the same shared hero markup as the other views. In the Components view, product identity/package data stays in the Component column, package children stay under Package contents, and the **Open** action is isolated in a dedicated **Actions** column. Stable channels are shown with the green success badge.

Core administrator responsiveness is container-aware. The `.xdecaro-suite` root is an inline-size container, so an expanded Joomla administrator sidebar can reduce the available content width without leaving Core stuck in a wider tablet/desktop layout. Components, All extensions and Updates use structured responsive record cards in constrained content areas; Diagnostics and Information collapse their internal grids and definition rows using the same available-width contract.

Installed state is read from Joomla `#__extensions`. Package contents are resolved from each installed Joomla package manifest first and use `package_id` only as a fallback, so legacy or stale package relationships do not mix extensions from different products.

The All extensions view translates Joomla manifest language keys when available and falls back to a readable label derived from the technical element when a translation is unavailable, preventing raw identifiers such as `COM_XDECAROCORE` from being presented as extension names.

The product catalog is a release-time baseline for products that are not installed yet. For installed extensions, a newer version already discovered by Joomla's updater takes precedence over the bundled catalog value. The dashboard deliberately does not perform remote network requests on every administrator page load.

Core 1.5.6 registers its administrator-specific assets through Joomla's Web Asset Manager using the canonical component URIs `com_xdecarocore/admin.css` and `com_xdecarocore/admin.js`; Joomla resolves those to the standard media `css/` and `js/` directories.

### Licensing during development

Core `1.5.6` does **not** enforce commercial licensing. The Information screen only reports that licensing is deferred while the suite is still under development. Installed components, updates and features are not blocked by a license check in this release.

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
