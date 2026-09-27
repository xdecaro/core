# Core by xdecaro

**Core by xdecaro** is the shared, domain-neutral technical foundation for the xdecaro Joomla ecosystem.

Core `2.2.0` adds the public, opt-in **Shared Admin UI** contract. It promotes reusable administrator layout and responsive patterns into Core so People, Courses, Organizations, Membership, Competitions, Photos and other products can share the same visual/mobile foundation without copying `com_xdecarocore` private CSS.

## Platform

Core 2.2.0 targets:

- **Joomla 6.1.3 only**;
- **PHP 8.3 or newer**.

## Package

Core is distributed through the canonical Joomla package `pkg_core` and contains exactly three child extensions:

- `lib_xdecarocore` — shared PHP contracts/services under `xdecaro\Core`;
- `com_xdecarocore` — the administrator component displayed as **xdecaro**;
- `plg_system_xdecarocore` — the lightweight system integration point and owner of public shared UI assets.

The legacy `pkg_xdecarocore` identity is retained only as an updater bridge for older installations. The canonical package identity remains `pkg_core`.

## Installation and updates

Install the versioned package ZIP through Joomla:

`pkg_core_2.2.0.zip`

The package uses Joomla's normal upgrade path. Core 2.2.0 is designed to update the published 2.1.x canonical package while preserving child extensions, configuration and the Core 2.1.1 Atum sidebar-width correction.

The canonical update feed is `updates/pkg_core.xml`. Releases are built deterministically and verified with SHA-256. The legacy `updates/pkg_xdecarocore.xml` feed points eligible old package registrations toward the same canonical package artifact.

## xdecaro administrator dashboard

After Core is installed, Joomla exposes **Components → xdecaro**.

The dashboard provides:

- **Dashboard** — ecosystem summary and immediate diagnostics;
- **Components** — known xdecaro products and installation/update state;
- **All extensions** — technical Joomla extensions detected for the ecosystem;
- **Updates** — installed-versus-available comparison using Joomla's local update cache;
- **Diagnostics** — Core presence, partial installations and disabled package children;
- **Information** — Core/Joomla/PHP runtime information;
- **Guide** — internal usage guide from Joomla's native toolbar.

Installed state is read from Joomla `#__extensions`. Package contents are resolved from installed package manifests first and use `package_id` only as a fallback. The dashboard does not perform remote network requests on every page load.

## Shared Web Asset Manager API

Canonical API:

```php
use xdecaro\Core\Asset\AssetService;

$webAssets = $this->getDocument()->getWebAssetManager();
$coreAssets = new AssetService();

if ($coreAssets->useAdminUi($webAssets)) {
    // Shared administrator UI is available for this view.
}
```

Public style dependency chain:

```text
xdecaro.core
    ↓
xdecaro.components
    ↓
xdecaro.admin
```

Public asset identifiers:

- `xdecaro.core` — design tokens, typography foundation, theme variables and base scope;
- `xdecaro.components` — small reusable cards, buttons, badges, fields, tables, empty states, loaders and modal shell;
- `xdecaro.admin` — shared administrator page structure and responsive patterns.

Assets are **opt-in** and never injected globally. Consumers scope migrated views with:

```html
<div class="xdecaro-scope xdecaro-suite">
    ...
</div>
```

Core shared UI must not globally restyle Joomla Atum.

## Shared Admin UI 2.2

The public `xdecaro.admin` layer provides domain-neutral patterns for:

- hero/page header/eyebrow hierarchy;
- KPI/metric grids;
- sections and section actions;
- information cards, definitions, diagnostics, notices and summaries;
- responsive administrative forms;
- Joomla `.control-group`, `.control-label` and `.controls` bridge scoped under `.xdecaro-form`;
- filter bars;
- accordion presentation using Joomla/Bootstrap collapse behavior;
- responsive technical tables and `data-label` stacked/card presentation.

Core-specific product, extension and updater table rules remain private to `com_xdecarocore`.

### Responsive contract

The shared UI is designed for effective administrator content widths of at least:

- 320 px;
- 393 px;
- 430 px;
- 768 px;
- 1024 px;
- wide desktop.

The contract accounts for Joomla's sidebar reducing real component width even when the browser viewport is wider. Important grid/flex children can shrink, long UUID/hash/filename values wrap, mobile form labels stack above full-width controls, and large tables either scroll only inside their wrapper or switch to the documented stacked presentation.

Do not use page-wide `overflow-x: hidden` to conceal responsive defects.

The reviewed Atum content-width correction introduced in Core 2.1.1 remains in the shared foundation and is covered by its regression workflow.

## Consumer migration

Core 2.2.0 publishes the contract; consumers migrate independently.

Recommended sequence:

1. People;
2. Courses;
3. Organizations;
4. Membership;
5. Competitions;
6. Photos;
7. remaining xdecaro products as reviewed.

A product should remove local CSS only when Core provides the same generic behavior. Product-specific workflows and domain layouts remain local.

Detailed API and migration guidance: `docs/asset-service.md`.

## Cross-product integration contracts

Canonical contracts include:

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

Core does not persist another product's relations, business state, events, notifications, tasks, metrics or reports. Each product owns its data and ACL. A cross-product reference never grants authorization.

## Naming

Public product name: **Core by xdecaro**.

Current technical identities:

- package `pkg_core`;
- administrator component `com_xdecarocore`;
- library `lib_xdecarocore`;
- system plugin `plg_system_xdecarocore`;
- repository `xdecaro/core`;
- PHP namespace `xdecaro\Core`.

The old package element `pkg_xdecarocore` is compatibility/updater metadata only and is not the canonical package identity.

## Build integrity

`VERSION` is the release source of truth. `bash build/build.sh` validates PHP/XML/JSON metadata, Joomla/PHP platform requirements, canonical package identity, shared/private UI contracts, dashboard/integration behavior, deterministic ZIP output and package contents, then writes `dist/SHA256SUMS.txt`.

Runtime workflows install and upgrade the package on Joomla 6.1.3 and preserve the canonical package migration from older Core states.

## Development principles

- Joomla 6.1.3 APIs only;
- PHP 8.3+;
- server-side ACL and CSRF where applicable;
- bound database queries;
- Web Asset Manager for shared assets;
- Semantic Versioning;
- opt-in, scoped shared UI;
- backward-compatible public APIs and asset identifiers within their supported release contract;
- clean install/update paths;
- no destructive database migrations;
- no private-table coupling between products;
- responsive/mobile behavior treated as part of the shared UI API.
