# AssetService and shared UI contract

Core by xdecaro exposes a public, opt-in Web Asset Manager API for xdecaro extensions. Core 2.2.0 adds the shared administrator layer `xdecaro.admin` while preserving the existing foundation and component contracts.

## Goals

- avoid duplicated generic CSS between products;
- use Joomla Web Asset Manager instead of direct stylesheet injection;
- keep shared assets opt-in and screen-specific;
- prevent Core from changing unrelated Joomla administrator UI;
- provide stable asset identifiers and responsive contracts for gradual migrations;
- make mobile behavior part of the shared UI contract rather than a per-product patch.

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
- `useComponents(WebAssetManager $webAssets): bool` enables shared UI primitives and their foundation dependency;
- `useAdminUi(WebAssetManager $webAssets): bool` enables the shared administrator layer and, through WAM dependencies, the complete `xdecaro.core → xdecaro.components → xdecaro.admin` chain.

The boolean return value lets an optional consumer preserve its local UI when Core media is unavailable instead of failing with an unknown asset error.

## Administrator usage

```php
use xdecaro\Core\Asset\AssetService;

$webAssets = $this->getDocument()->getWebAssetManager();
$coreAssets = new AssetService();

if ($coreAssets->useAdminUi($webAssets)) {
    // The complete shared administrator UI is available for this view.
}
```

Canonical administrator scope:

```html
<div class="xdecaro-scope xdecaro-suite">
    <header class="xdecaro-suite__hero">
        ...
    </header>
    <section class="xdecaro-suite__section">
        ...
    </section>
</div>
```

Do not add `.xdecaro-scope` around the whole Joomla administrator application. Scope only the product view that deliberately consumes the shared contract.

## Asset layers

The system plugin installs:

- `media/plg_system_xdecarocore/joomla.asset.json`;
- `media/plg_system_xdecarocore/css/core.css`;
- `media/plg_system_xdecarocore/css/components.css`;
- `media/plg_system_xdecarocore/css/admin.css`.

Dependency order is:

```text
xdecaro.core
    ↓
xdecaro.components
    ↓
xdecaro.admin
```

The registry is loaded through Joomla `WebAssetRegistry::addExtensionRegistryFile()` only when a consumer calls `AssetService`. Public WAM identifiers are API contracts.

## Foundation and small components

Foundation variables remain scoped under `.xdecaro-scope` and use the `--xdecaro-*` prefix.

Small shared primitives include:

- cards and card sections;
- toolbar;
- buttons;
- badges;
- fields, help text and explicit input/select/textarea classes;
- basic table wrapper/table;
- empty state;
- loader;
- modal shell.

These classes provide presentation only. They do not implement product behavior, ACL, validation or domain workflows.

## Shared administrator primitives

`xdecaro.admin` publishes domain-neutral administrator patterns including:

- `.xdecaro-suite`;
- hero, page header and eyebrow;
- KPI/metric grids;
- sections, section actions and count badges;
- information cards and definition lists;
- diagnostics, notices and summary bars;
- `.xdecaro-form` and responsive form grids;
- Joomla `.control-group/.control-label/.controls` bridge scoped under `.xdecaro-form`;
- `.xdecaro-filterbar`;
- `.xdecaro-accordion` presentation;
- responsive table wrapper and `data-label` stacked/card behavior.

Core-specific product, extension and updater-table selectors remain private to `com_xdecarocore` and are not public UI API.

## Responsive contract

Shared administrator UI must remain usable at effective content widths of at least 320, 393, 430, 768 and 1024 px, plus wide desktop.

Important rules:

- flex/grid children that contain product content must be shrinkable (`min-width: 0` where required);
- cards, forms and sections must stay within available content width;
- long UUIDs, hashes and filenames must wrap rather than widen the page;
- mobile Joomla forms stack label above a full-width control;
- Choices/select/calendar/input/textarea wrappers inside `.xdecaro-form` must respect available width;
- KPI grids are generic and do not assume five cards; full-row final-card behavior is opt-in through a modifier;
- large tables either scroll only inside their dedicated wrapper or use the documented `data-label` responsive-card contract;
- do not use page-wide `overflow-x: hidden` to conceal layout defects;
- Core shared UI must not reposition Joomla Atum header/sidebar chrome.

Container-aware behavior is preferred for complex patterns because Joomla's open sidebar can leave a narrow component area even on a wide browser.

## Accordion behavior

Core supplies accordion presentation classes only. Consumers should continue to use Joomla/Bootstrap collapse behavior, including native `aria-expanded`, `aria-controls` and focus semantics, instead of introducing a second proprietary runtime.

## Accessibility and theme

Shared UI preserves visible focus states, semantic reading order, touch-friendly controls, reduced-motion preferences and status labels that do not depend on color alone.

Base tokens follow Joomla variables where reliable and retain light/dark fallbacks. Consumers may override `--xdecaro-*` variables on their own `.xdecaro-scope` without editing Core files.

## Migration rule

Migrate a product incrementally:

1. require a Core version that exposes `AssetService::useAdminUi()`;
2. load the shared admin asset on one reviewed administrator view;
3. wrap the view in `.xdecaro-scope xdecaro-suite`;
4. replace only genuinely generic local layout/UI primitives;
5. keep domain-specific CSS and behavior local to the product;
6. verify desktop and effective widths 1024, 768, 430, 393 and 320 px;
7. remove local duplicate CSS only after the shared replacement is proven.

Core remains domain-neutral. People, Courses, Organizations, Membership, Competitions, Photos and other products are independent consumers; Core must never depend on their private tables or business logic.
