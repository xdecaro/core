# Core 1.4.x stabilization

Core `1.4.0` is the active ecosystem baseline.

The line is intentionally frozen to `1.4.x` while the namespace migration, real Joomla runtime matrix and first Capability Registry integrations are validated.

## Why 1.4.x

Core `1.4.0` was already published after `1.3.0`. It adds the in-memory, domain-neutral `CapabilityRegistry` without changing product state, database ownership or dependency direction. Rolling back the update channel to `1.3.x` would violate release monotonicity and create more risk than stabilizing the already-published minor.

## Allowed during stabilization

- compatible bug fixes;
- security fixes;
- CI/runtime validation improvements;
- documentation corrections;
- compatibility fixes for Joomla versions already claimed;
- additive tests for existing public contracts.

These changes normally use PATCH releases.

## Not allowed without reopening the minor line

- product-domain services in Core;
- persistent central relation/capability tables without multi-product evidence;
- mandatory dependencies from Core to another product;
- renaming existing public contracts;
- removal of the temporary `Xdecaro\Core` compatibility library;
- publishing Core `1.5.0` only to support one consumer.

## Mechanical guard

The repository root contains `STABILIZATION_SERIES`. Core CI verifies that `VERSION` belongs to that series. A future minor release therefore requires an explicit, reviewable change to both the version and stabilization marker.

## Exit criteria

The `1.4.x` freeze can be reconsidered only after:

1. ecosystem namespace audit is green;
2. Joomla runtime smoke is green on the supported majors;
3. the first provider-owned capability integrations are working without private-table coupling;
4. at least two products demonstrate a genuinely shared missing Core primitive before that primitive is added to Core.
