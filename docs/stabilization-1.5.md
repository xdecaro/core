# Core 1.5 stabilization

Core `1.5.x` is the stabilization line for the central xdecaro administrator dashboard.

## Scope

The 1.5 line adds only ecosystem-level administration and diagnostics that remain domain-neutral:

- `com_xdecarocore`, displayed as **xdecaro** in Joomla;
- inventory of known xdecaro products;
- installed state and manifest version discovery through Joomla `#__extensions`;
- package-child grouping through Joomla `package_id`;
- comparison against the release-time Core catalog and Joomla's local update cache;
- local diagnostics for partial package installations and disabled package plugins/modules;
- responsive, scoped light/dark administrator UI.

Core does not read or write another product's private data tables. The dashboard inspects Joomla extension metadata only.

## Compatibility

Core `1.5.0` preserves all public contracts from 1.4.0, including `EntityReference`, `RelationReference`, `Capability`, `CapabilityRegistry`, `IntegrationEvent` and the existing Web Asset Manager identifiers.

The temporary `Xdecaro\\Core` compatibility library remains packaged while current consumers migrate to canonical `xdecaro\\Core`.

The package upgrade path is:

`Core 1.4.0 -> Core 1.5.0`

The upgrade adds `com_xdecarocore` without removing the existing libraries or system plugin.

## Runtime gate

`.github/workflows/dashboard-runtime.yml` validates both clean installation and the 1.4.0 -> 1.5.0 upgrade on pinned Joomla 5.4.8 and Joomla 6.1.3 environments. The gate verifies:

1. all expected Core package children are registered at version 1.5.0;
2. the system plugin remains enabled;
3. the administrator component is linked to `pkg_xdecarocore`;
4. Joomla creates an administrator menu entry for `com_xdecarocore`;
5. the component boots through Joomla's real extension namespace map;
6. `EcosystemService` discovers Core 1.5.0 and the installed technical extensions from Joomla.

The existing ecosystem runtime matrix continues to validate the published 1.4.0 compatibility baseline used by already-released consumers. Core 1.5.0 is additive to those contracts.

## Licensing

Commercial licensing is intentionally out of scope for 1.5.x stabilization. The dashboard may report that licensing is deferred, but no installed feature, component or update is blocked by a license check.

## Release rule

During the 1.5 stabilization line:

- compatible fixes use PATCH releases (`1.5.1`, `1.5.2`, ...);
- product-specific business logic remains outside Core;
- new dashboard product metadata must not introduce runtime dependencies on those products;
- network access must not be performed on every administrator page load;
- the dashboard must remain usable when Core is the only xdecaro product installed.
