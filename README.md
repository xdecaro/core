# Core by xdecaro

**Core by xdecaro** is the shared, domain-neutral technical foundation for the xdecaro Joomla ecosystem.

Core **2.2.0** targets **Joomla 6.1.3 only** and requires **PHP 8.3+**. It preserves the canonical `pkg_core` package introduced by Core 2.1.0 and adds a public, opt-in administrator UI layer so independent products can share the same responsive visual language without copying Core component CSS.

## Package

The canonical Joomla package is:

```text
pkg_core_2.2.0.zip
```

It contains exactly three child extensions:

- `lib_xdecarocore` — public PHP contracts/services under `xdecaro\Core`;
- `com_xdecarocore` — the xdecaro administrator dashboard;
- `plg_system_xdecarocore` — lightweight system integration and shared public media assets.

The historical `pkg_xdecarocore` identity is recognized only as a migration/update bridge. New installations and releases use `pkg_core`.

## Shared Web Asset Manager API

Canonical API:

```php
use xdecaro\Core\Asset\AssetService;

$webAssets = $this->getDocument()->getWebAssetManager();
$coreAssets = new AssetService();

$coreAssets->useFoundation($webAssets); // xdecaro.core
$coreAssets->useComponents($webAssets); // xdecaro.components + foundation
$coreAssets->useAdminUi($webAssets);    // xdecaro.admin + components + foundation
```

Public asset dependency chain:

```text
xdecaro.core
    ↓
xdecaro.components
    ↓
xdecaro.admin
```

Assets are **opt-in**. Core does not inject its CSS globally into Joomla administrator pages.

## Shared Admin UI — Core 2.2.0

A migrated administrator view uses a scoped wrapper such as:

```html
<div class="xdecaro-scope xdecaro-suite">
    ...
</div>
```

`xdecaro.admin` provides domain-neutral presentation for:

- page hero/header and eyebrow context;
- KPI/metric grids;
- cards, sections, summaries, notices and diagnostics;
- responsive Joomla forms (`.control-group`, labels and controls) inside `.xdecaro-form`;
- one/two-column form grids;
- filter bars;
- accordion presentation while Joomla/Bootstrap owns collapse behavior;
- responsive tables, including wrapper-only scrolling and optional `data-label` stacked/card mode;
- long UUID/hash/filename wrapping;
- focus-visible and reduced-motion behavior.

The public UI is container-aware. It is designed for effective content widths including **320, 393, 430, 768 and 1024 px**, because Joomla's administrator sidebar can leave a narrow content column even when the browser viewport itself is wider.

Core does not use page-wide `overflow-x: hidden` to hide layout defects and does not restyle Atum's global header/sidebar through the shared asset.

## Administrator dashboard

After installation Joomla exposes **Components → xdecaro**.

The dashboard provides:

- **Dashboard** — ecosystem summary and immediate diagnostics;
- **Components** — known xdecaro products and package contents;
- **All extensions** — detected Joomla packages/components/plugins/libraries/modules;
- **Updates** — installed-versus-discovered versions;
- **Diagnostics** — Core/package health;
- **Information** — runtime/version information;
- **Guide** — internal administrator guide.

From 2.2.0 the Core component consumes the same public `xdecaro.admin` asset exposed to other products. Its local `com_xdecarocore/admin.css` contains only Core-dashboard-specific presentation such as package expansion rows and extension/update details. The former private `responsive.css` layer is retired.

## Cross-product integration contracts

Core remains domain-neutral. Public integration contracts include:

- `xdecaro\Core\Integration\EntityReference`;
- `xdecaro\Core\Integration\RelationReference`;
- `xdecaro\Core\Integration\Capability`;
- `xdecaro\Core\Integration\CapabilityRegistry`;
- `xdecaro\Core\Integration\IntegrationEvent`;
- location provider/service contracts under `xdecaro\Core\Location`.

Core does not own People, Courses, Organizations, Membership, Competitions, Photos or other products' business data, ACL or workflows. Consumers integrate through public APIs/capabilities, not another product's private tables.

## Consumer migration order

Core 2.2.0 establishes the UI contract. Product migration happens independently, beginning with:

1. People;
2. Courses;
3. Organizations;
4. Membership;
5. Competitions;
6. Photos;
7. remaining products after review.

Local product CSS remains valid for domain-specific presentation. The goal is to remove duplicated **shared UI foundation**, not all local CSS.

## Installation and updates

Install the canonical versioned package through Joomla's normal extension installer. The package registers:

```text
https://raw.githubusercontent.com/xdecaro/core/main/updates/pkg_core.xml
```

The retained `updates/pkg_xdecarocore.xml` feed acts only as a bridge for installations that still know the historical package identity; it resolves to the canonical `pkg_core` package.

Releases are deterministic and verified with SHA-256.

## Build and verification

`VERSION` is the release source of truth.

```bash
bash build/build.sh
```

The build requires PHP 8.3+, runs source/contract tests, validates the canonical package migration and Core 2.2 shared UI contract, creates deterministic ZIP files and writes `dist/SHA256SUMS.txt`.

CI additionally verifies Joomla **6.1.3** clean/upgrade runtime scenarios, installed public media (`xdecaro.admin`) and the absence of the retired component `responsive.css`.

## Technical identifiers

- package: `pkg_core`;
- administrator component: `com_xdecarocore`;
- library: `lib_xdecarocore`;
- system plugin: `plg_system_xdecarocore`;
- PHP namespace: `xdecaro\Core`;
- repository: `xdecaro/core`.

See `docs/asset-service.md` for the public UI loading contract and `docs/superpowers/specs/2026-09-27-core-2.2.0-shared-admin-ui-design.md` for the Core 2.2 design specification.
