# AssetService and shared UI contract

Core exposes opt-in Joomla Web Asset Manager APIs for xdecaro extensions. Core 2.2.0 adds the public administrator layout layer used to keep desktop and mobile UI consistent across independently released products.

## Goals

- avoid duplicated generic CSS between products;
- use Joomla Web Asset Manager instead of direct stylesheet injection;
- keep shared assets opt-in and screen-specific;
- prevent Core from changing unrelated Joomla administrator UI;
- provide stable asset identifiers and responsive contracts for gradual migrations.

## Public PHP API

Canonical class: `xdecaro\Core\Asset\AssetService`

Stable constants:

- `AssetService::REGISTRY_EXTENSION` = `plg_system_xdecarocore`;
- `AssetService::STYLE_FOUNDATION` = `xdecaro.core`;
- `AssetService::STYLE_COMPONENTS` = `xdecaro.components`;
- `AssetService::STYLE_ADMIN` = `xdecaro.admin`.

Methods:

- `isAvailable(): bool` checks whether the installed Core media registry exists;
- `register(WebAssetManager $webAssets): bool` registers the Core asset registry once when needed;
- `useFoundation(WebAssetManager $webAssets): bool` enables design tokens/foundation only;
- `useComponents(WebAssetManager $webAssets): bool` enables reusable control primitives;
- `useAdminUi(WebAssetManager $webAssets): bool` enables the complete administrator UI dependency chain.

The dependency order is:

```text
xdecaro.core
    ↓
xdecaro.components
    ↓
xdecaro.admin
```

The boolean return value lets an optional consumer preserve a local fallback when Core media is unavailable instead of failing with an unknown asset error.

## Administrator usage

```php
use xdecaro\Core\Asset\AssetService;

$webAssets = $this->getDocument()->getWebAssetManager();
$coreAssets = new AssetService();

if ($coreAssets->useAdminUi($webAssets)) {
    // The full shared administrator UI contract is available for this view.
}
```

Canonical page scope:

```html
<div class="xdecaro-scope xdecaro-suite">
    <header class="xdecaro-suite__page-header">
        <span class="xdecaro-suite__eyebrow">Area</span>
        <h1>Page title</h1>
    </header>

    <section class="xdecaro-suite__section xdecaro-card">
        <div class="xdecaro-card__body">...</div>
    </section>
</div>
```

Do not put `.xdecaro-scope` around the entire Joomla administrator unless the whole product view has deliberately migrated. Core never injects these assets globally and `xdecaro.admin` must not restyle Atum header/sidebar chrome.

## Asset registry

The system plugin installs:

- `media/plg_system_xdecarocore/joomla.asset.json`;
- `media/plg_system_xdecarocore/css/core.css`;
- `media/plg_system_xdecarocore/css/components.css`;
- `media/plg_system_xdecarocore/css/admin.css`.

Public WAM identifiers are API contracts. Consumers must not load CSS from `com_xdecarocore` directly.

## Public administrator primitives

`xdecaro.admin` provides domain-neutral presentation for:

- page hero/header/eyebrow;
- KPI/metric grids;
- sections, info cards, diagnostics, notices and summaries;
- `.xdecaro-form` and Joomla `.control-group` responsive bridge;
- one/two-column form grids;
- `.xdecaro-filterbar`;
- `.xdecaro-accordion` presentation while Joomla/Bootstrap owns collapse behavior;
- responsive table wrappers and optional `data-label` stacked/card presentation;
- long technical-value wrapping, focus-visible and reduced-motion behavior.

Product-specific workflows, ACL, validation, domain tables and custom interactions stay in the consuming component.

## Responsive contract

Mobile is part of the public API, not an optional product override. Shared UI is designed for effective content widths including 320, 393, 430, 768 and 1024 px. Container queries are used where Joomla's sidebar can make the real content column narrower than the browser viewport.

Rules include:

- flex/grid children remain shrinkable with `min-width: 0` where required;
- cards/forms never force the whole administrator page wider than the available content area;
- standard Joomla form labels stack above controls in narrow `.xdecaro-form` containers;
- input/select/textarea/Choices/calendar controls stay within the available width;
- UUIDs, hashes and filenames wrap instead of widening the page;
- a large table may scroll inside `.xdecaro-suite__responsive-wrap`, never by forcing page-level horizontal scrolling;
- tables opting into `data-responsive="cards"` retain column context through `data-label`.

Do not use page-wide `overflow-x: hidden` as a workaround for a broken child layout.

## Foundation and themes

Foundation variables are scoped under `.xdecaro-scope` and use the `--xdecaro-*` prefix. Shared UI follows Joomla/Core variables for light/dark compatibility. Consumers may override tokens on their own scope without editing Core files.

## Migration rule

Migrate products incrementally:

1. load `useAdminUi()` only on a selected administrator view;
2. add `.xdecaro-scope xdecaro-suite` to that view;
3. replace genuinely generic local primitives with the public Core equivalents;
4. verify desktop plus 320/393/430/768/1024 effective widths and theme/focus behavior;
5. keep product-specific CSS for domain-specific presentation;
6. remove local shared-foundation CSS only after equivalent Core behavior is proven.

People is the first planned external migration, followed by Courses, Organizations, Membership, Competitions and Photos. These products remain independent consumers; Core must never depend on their business logic.
