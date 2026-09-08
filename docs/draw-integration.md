# Draw integration with Core by xdecaro

## Purpose

Draw is a product-domain component. Core supports it only through domain-neutral infrastructure.

The integration must preserve the boundary:

`Draw -> Core by xdecaro`

Core must never depend on Draw.

## Existing Core APIs Draw should use

Canonical Core 1.3+ APIs include:

- `xdecaro\Core\Integration\EntityReference`;
- `xdecaro\Core\Integration\RelationReference`;
- `xdecaro\Core\Integration\Capability` / `IntegrationEvent` where a genuinely generic integration needs them;
- `xdecaro\Core\Asset\AssetService`;
- `xdecaro.core` and `xdecaro.components` assets;
- `.xdecaro-*` UI primitives and `--xdecaro-*` design tokens.

The former `Xdecaro\Core` spelling is compatibility-only for already-published consumers. New Draw code uses lowercase `xdecaro`.

No Draw-specific Core service is required.

## What must remain outside Core

Do not add the following to Core:

- draw sessions;
- pots/fasce;
- seeds;
- draw algorithms;
- random selection rules;
- group/bracket assignment rules;
- draw constraints;
- reveal sequence;
- unrevealed/revealed live state;
- draw audit history;
- Draw-specific event names;
- Draw-specific result payload parsing.

These are Draw-domain responsibilities.

## Cross-product references

Draw should represent external source entities through Core `EntityReference` values.

Example source competition:

```php
use xdecaro\Core\Integration\EntityReference;

$competition = new EntityReference('com_xdecarocompetitions', 'competition', 42);
$participant = new EntityReference('com_xdecarocompetitions', 'participant', 10);
```

These references do not authorize Draw to query Competitions private tables. Draw must not read or write `#__xdecarocompetitions_*` directly.

Current Draw component identity is `com_xdecarodraw`; Draw-owned tables use `#__xdecarodraw_*`.

## Relations

Where a product needs to retain a relationship to a draw/result, use a stable public relation or a product-owned integration record rather than a cross-component database foreign key. Core does not persist the relationship automatically.

## Shared UI

Draw administrator screens should opt into Core shared assets rather than duplicating generic buttons, cards, badges, tables, alerts, loading states, modal shells and design tokens.

Draw-specific live presentation, animation, pot visuals, group layouts and bracket transitions remain local to Draw.

## Live transport

Core should not gain a Draw-specific realtime transport merely to support Draw. Draw can begin with Joomla-compatible AJAX/polling.

If multiple products later prove the same domain-neutral need for SSE/WebSocket transport, reconnect logic, sequence cursors or generic event-stream infrastructure, that capability can be evaluated separately. Do not move it into Core speculatively.

## AJAX, ACL and CSRF

Using shared infrastructure never transfers authorization responsibility to Core. Draw remains responsible for:

- draw ACL;
- Joomla CSRF validation on state-changing actions;
- state-transition validation;
- entry/slot validation;
- protection against result manipulation;
- preventing disclosure of unrevealed assignments.

## Diagnostics

Generic Core diagnostics may report extension/dependency availability, but Core must not inspect Draw private state or judge whether a draw is valid.

Draw diagnostics own schema, audit integrity, source integration availability and supported dependency checks.

## Public event contracts

Draw-specific events such as `draw.entry.revealed` remain owned and versioned by Draw. `IntegrationEvent` can envelope such an event for optional consumers without making the event name Core-owned.

## Compatibility

When Draw consumes Core:

1. verify the real minimum Core version;
2. declare mandatory/optional dependency policy coherently;
3. show controlled administrator behavior for missing/incompatible dependencies;
4. use only documented Core APIs;
5. keep Draw domain state independent from Core storage;
6. keep Competitions optional and accessed only through public integration boundaries.

Draw is still a prerelease product. Runtime Joomla compatibility remains unproven until tested on supported Joomla installations.
