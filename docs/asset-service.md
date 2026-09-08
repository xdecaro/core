# AssetService and shared UI contract

Core by xdecaro 1.1.0 introduced the first public Web Asset Manager API for xdecaro extensions. From Core 1.3.0, the canonical PHP namespace uses lowercase `xdecaro`.

## Goals

- avoid duplicated generic CSS between products;
- use Joomla Web Asset Manager instead of direct stylesheet injection;
- keep shared assets opt-in and screen-specific;
- prevent Core from changing unrelated Joomla administrator UI;
- provide stable asset identifiers and CSS contracts for gradual migrations.

## Public PHP API

Canonical class: `xdecaro\Core\Asset\AssetService`

The former `Xdecaro\Core\Asset\AssetService` spelling is temporarily autoload-compatible for already-published consumers, but new code must use the lowercase vendor namespace.

Stable constants:

- `AssetService::REGISTRY_EXTENSION` = `plg_system_xdecarocore`;
- `AssetService::STYLE_FOUNDATION` = `xdecaro.core`;
- `AssetService::STYLE_COMPONENTS` = `xdecaro.components`.

Methods:

- `isAvailable(): bool` checks whether the installed Core media registry exists;
- `register(WebAssetManager $webAssets): bool` registers the Core asset registry once when needed;
- `useFoundation(WebAssetManager $webAssets): bool` enables design tokens/foundation only;
- `useComponents(WebAssetManager $webAssets): bool` enables shared UI primitives and their foundation dependency.

The boolean return value lets an optional consumer preserve its existing local UI when Core media is unavailable instead of failing with an unknown asset error.

## Usage

```php
use xdecaro\Core\Asset\AssetService;

$webAssets = $this->getDocument()->getWebAssetManager();
$coreAssets = new AssetService();

if ($coreAssets->useComponents($webAssets)) {
    // Core UI assets are available for this view.
}
```

The consuming markup must scope Core-managed UI explicitly:

```html
<div class="xdecaro-scope">
    <section class="xdecaro-card">
        <div class="xdecaro-card__body">
            <button class="xdecaro-button xdecaro-button--primary" type="button">
                Save
            </button>
        </div>
    </section>
</div>
```

Do not add `.xdecaro-scope` around the entire Joomla administrator unless the whole view has deliberately migrated to Core styles.

## Asset registry

The system plugin installs:

- `media/plg_system_xdecarocore/joomla.asset.json`;
- `media/plg_system_xdecarocore/css/core.css`;
- `media/plg_system_xdecarocore/css/components.css`.

The registry is loaded through Joomla `WebAssetRegistry::addExtensionRegistryFile()` only when a consumer calls `AssetService`.

Public WAM identifiers are API contracts. Do not rename or remove them in a PATCH/MINOR release.

## CSS scope and tokens

Foundation variables are declared only under `.xdecaro-scope` and use the `--xdecaro-*` prefix.

The first shared primitives are:

- `.xdecaro-card` and card sections;
- `.xdecaro-toolbar`;
- `.xdecaro-button` plus primary/danger variants;
- `.xdecaro-badge` plus success/warning/danger variants;
- `.xdecaro-field`, labels/help text and explicit input/select/textarea classes;
- `.xdecaro-table-wrap` and `.xdecaro-table`;
- `.xdecaro-empty`;
- `.xdecaro-loader`;
- `.xdecaro-modal` shell.

These classes provide visual structure only. They do not implement product behavior, ACL, validation, form workflows or modal JavaScript lifecycle.

## Light and dark mode

Base tokens follow Joomla variables where reliable and include fallbacks. Dark tokens recognize common Joomla/Bootstrap dark attributes and an explicit `data-xdecaro-theme="dark"` override. `data-xdecaro-theme="light"` can force the light token set for an intentionally isolated scope.

Consumers may override `--xdecaro-*` variables on their own `.xdecaro-scope` without editing Core files.

## Migration rule

Migrate a product incrementally:

1. load `AssetService` on one low-risk view;
2. wrap only that view/section in `.xdecaro-scope`;
3. replace genuinely generic local primitives with `.xdecaro-*` equivalents;
4. compare desktop/tablet/smartphone and light/dark behavior;
5. keep local fallback code until the Core replacement is proven;
6. do not migrate product-specific builder/workflow styles merely for consistency.

Forms, Courses and Competitions remain independent consumers. Core must never depend on them.
