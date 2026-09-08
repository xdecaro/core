# Draw integration with Xdecaro Core

## Purpose

Draw is a product-domain component. Core supports it only through domain-neutral infrastructure.

The integration must preserve the Core boundary:

`Draw -> Xdecaro Core`

Core must never depend on Draw.

## Existing Core APIs Draw should use

Draw can use the current Core public APIs for:

- `Xdecaro\Core\Integration\EntityReference`;
- `Xdecaro\Core\Integration\RelationReference`;
- shared Web Asset Manager registration;
- `xdecaro.core` and `xdecaro.components` assets;
- `.xdecaro-*` UI primitives and `--xdecaro-*` design tokens;
- dependency/version diagnostics already exposed by Core.

No Draw-specific Core service is required for the initial architecture.

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

These are product-domain responsibilities and belong to Draw.

## Cross-product references

Draw should represent external source entities through Core `EntityReference` values where possible.

Example source competition:

```php
use Xdecaro\Core\Integration\EntityReference;

$competition = new EntityReference('com_decarodcl', 'competition', 42);
```

Example participant:

```php
$participant = new EntityReference('com_decarodcl', 'participant', 10);
```

These references do not allow Draw to query Competitions private tables. They only provide stable cross-product identity.

## Relations

Where a product needs to retain a relationship to a draw/result, use a stable relation or product-owned integration record rather than a cross-component database foreign key.

Core does not persist the relationship automatically.

Data ownership remains with the individual products.

## Shared UI

Draw administrator screens should opt into Core shared assets rather than duplicating generic buttons, cards, badges, tables, alerts, loading states, modals and design tokens.

Draw-specific live presentation, animation, pot visuals, group layouts and bracket transitions remain local to Draw.

The recommended boundary is:

- generic shell/primitives -> Core;
- draw-domain presentation -> Draw.

## Live transport

Core should not gain a Draw-specific realtime transport merely to support Draw.

Draw can begin with Joomla-compatible AJAX/polling.

If multiple Xdecaro products later prove a common need for SSE/WebSocket transport, reconnect logic, sequence cursors or generic event-stream infrastructure, that capability may be evaluated for a future Core minor release.

Do not move it into Core speculatively.

## AJAX and CSRF

Generic Joomla-compliant AJAX/CSRF helpers may belong to Core when they are already public and reusable.

Draw remains responsible for:

- draw ACL;
- state-transition validation;
- entry/slot validation;
- protection against result manipulation;
- preventing disclosure of unrevealed assignments.

Using a Core AJAX helper never transfers authorization responsibility to Core.

## Diagnostics

Core diagnostics may report whether Draw is installed and compatible only through generic extension/dependency registry mechanisms.

Core must not inspect Draw private database state or judge whether a particular draw is valid.

Draw's own Information/Diagnostics area should report Draw-domain checks such as:

- schema version;
- draw tables;
- audit/event integrity;
- source integration availability;
- supported Core version;
- Competitions adapter availability.

## Public event contracts

Draw-specific events such as `draw.entry.revealed` remain owned and versioned by Draw.

Only events that are demonstrably useful across multiple products and domain-neutral should be considered for Core.

## Compatibility

When Draw starts consuming a Core public API:

1. verify the real Core minimum version;
2. declare the dependency coherently in Draw manifests/package/update metadata if Core is mandatory;
3. show a controlled Joomla administrator message for missing/incompatible Core;
4. never assume an undocumented Core class/service exists;
5. keep Draw domain state independent from Core storage.

Because Draw is a new product, the first implementation should choose the Core dependency policy before the first stable release and keep it consistent thereafter.

## Current recommendation

For the initial Draw implementation:

- use Core 1.1.0+ public integration and UI APIs where they fit;
- do not add Draw-specific code to Core;
- keep Draw/Competitions integration optional;
- require all cross-product data exchange to use stable public contracts rather than private tables;
- revisit Core only when at least one additional real consumer proves the same generic infrastructure is needed.
