# Responsive list/detail layout

`xdecaro.layouts` provides domain-neutral responsive layout primitives for Xdecaro extensions. It does not own routing, data loading, selection state or product-specific business logic.

## Enable the asset

```php
use xdecaro\Core\Asset\AssetService;

$assets = new AssetService();
$assets->useLayouts($this->getDocument()->getWebAssetManager());
```

Use `useComponents()` separately when the screen also needs shared buttons, badges, cards or other component primitives.

## Base structure

```html
<div class="xdecaro-scope">
    <section class="xdecaro-list-detail xdecaro-list-detail--with-nav-context">
        <nav class="xdecaro-list-detail__pane xdecaro-list-detail__nav" aria-label="Primary navigation">
            ...
        </nav>

        <aside class="xdecaro-list-detail__pane xdecaro-list-detail__list" aria-label="Items">
            ...
        </aside>

        <main class="xdecaro-list-detail__pane xdecaro-list-detail__detail">
            ...
        </main>

        <aside class="xdecaro-list-detail__pane xdecaro-list-detail__context" aria-label="Context">
            ...
        </aside>
    </section>
</div>
```

Available modifiers:

- no modifier: `list + detail`;
- `xdecaro-list-detail--with-nav`: `nav + list + detail` on wide screens;
- `xdecaro-list-detail--with-context`: `list + detail + context` on wide screens;
- `xdecaro-list-detail--with-nav-context`: `nav + list + detail + context` on wide screens.

## Responsive contract

The layout follows three shared breakpoints:

- `>= 1280px`: optional navigation and context panes may remain visible beside list and detail;
- `560px–1279px`: navigation and context collapse, leaving list and detail side by side;
- `< 560px`: one pane is visible at a time.

On small screens the list is the default pane. A consumer can select another pane by changing `data-xdecaro-pane` on the root element:

```html
<section class="xdecaro-list-detail" data-xdecaro-pane="detail">
    ...
</section>
```

Supported values are `nav`, `list`, `detail` and `context`.

Core deliberately does not attach a global click handler. The consuming extension may change `data-xdecaro-pane` through its existing navigation/router or through a small local controller. This avoids duplicate listeners and keeps Core independent from product workflows.

## Pane structure

For screens that need fixed header/footer areas and a scrolling body, use:

```html
<div class="xdecaro-list-detail__pane xdecaro-list-detail__detail">
    <div class="xdecaro-list-detail__pane-inner">
        <header class="xdecaro-list-detail__header">...</header>
        <div class="xdecaro-list-detail__body">...</div>
        <footer class="xdecaro-list-detail__footer">...</footer>
    </div>
</div>
```

Elements intended only for the single-pane mobile presentation can use `xdecaro-list-detail__mobile-only`.

## Custom sizing

Consumers can override the layout without replacing Core CSS:

```css
.my-screen.xdecaro-list-detail {
    --xdecaro-list-detail-nav-width: 5rem;
    --xdecaro-list-detail-list-width: 21rem;
    --xdecaro-list-detail-context-width: 18rem;
    --xdecaro-list-detail-min-height: 36rem;
}
```

## Accessibility

The responsive CSS changes presentation only. Consumers remain responsible for semantic landmarks and accessible names that match their content. When JavaScript changes the active mobile pane, move focus deliberately when appropriate, preserve keyboard navigation and keep the selected item state exposed with normal HTML/ARIA semantics.

Do not use color alone to indicate selection or status.
