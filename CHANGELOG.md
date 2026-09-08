# Changelog

All notable changes to **Core by xdecaro** are documented here.

The project follows Semantic Versioning.

## 1.3.0 — 2026-09-08

### Changed

- Canonical public PHP namespace is now `xdecaro\Core`.
- Core system plugin namespace is now rooted at lowercase `xdecaro`.
- New code and documentation must use the lowercase vendor namespace.

### Compatibility

- Added temporary `lib_xdecarocorelegacy` package child registering the deprecated `Xdecaro\Core` prefix.
- The compatibility library is generated from the exact canonical source tree during each deterministic build; there is no second implementation of the Core API.
- Existing published consumers can continue to autoload the former namespace while they migrate.
- Package element, asset identifiers, public contract behavior and database footprint remain unchanged.
- Removing the legacy namespace mapping is reserved for a future major release after ecosystem migration is verified.

## 1.2.0 — 2026-09-08

### Added

- Public `Xdecaro\Core\Integration\Capability` contract for stable, versioned public capability identifiers.
- Public `Xdecaro\Core\Integration\IntegrationEvent` domain-neutral event envelope.
- Integration guidance for Notifications, Tasks and Analytics with optional dependencies and strict data ownership boundaries.
- Build-time smoke validation for capability/event serialization and required public integration classes.

### Architecture

- No Core database tables added.
- Core does not persist, queue or dispatch notifications, tasks, analytics data or product events.
- Existing `EntityReference`, `RelationReference` and shared UI APIs remain backward compatible.

## 1.1.0 — 2026-09-08

### Added

- Public `Xdecaro\Core\Asset\AssetService` for opt-in Joomla Web Asset Manager registration.
- Stable style asset identifiers `xdecaro.core` and `xdecaro.components`.
- Scoped `.xdecaro-scope` design tokens using the `--xdecaro-*` namespace.
- Shared cards, toolbars, buttons, badges, form controls, tables, empty/loading states and modal shell.
- Responsive and light/dark foundations without global selectors or automatic asset injection.
- AssetService smoke test and build-time validation of `joomla.asset.json` and packaged media files.

### Compatibility

- No database changes.
- Existing EntityReference and RelationReference APIs are unchanged.
- Technical package/plugin/library identifiers are unchanged.
- Assets are opt-in; existing product UI is unaffected until a consumer explicitly enables them.

## 1.0.1 — 2026-09-08

### Changed

- Standardized the public product name to **Core by xdecaro**.
- Updated Joomla-visible library, system plugin, package, update server and update-feed labels.
- Kept all technical identifiers and public PHP namespaces unchanged for backward compatibility.
- No runtime behavior, database structure or public integration API changed.

## 1.0.0 — 2026-09-08

### Added

- Initial `pkg_xdecarocore` package structure.
- Reusable `lib_xdecarocore` Joomla library.
- Lightweight `plg_system_xdecarocore` system plugin bootstrap.
- `Xdecaro\Core\Version` public version contract.
- `Xdecaro\Core\Integration\EntityReference` for stable cross-component entity references.
- `Xdecaro\Core\Integration\RelationReference` for typed cross-component relations.
- Cross-product integration contract documentation.
- Joomla extension update feed targeting Joomla 4, 5 and 6.
- Joomla changelog feed for the package updater.
- GitHub Release workflow with SHA-256 publication.
- Deterministic ZIP builds and `SHA256SUMS.txt` generation.
- CI validation on PHP 7.4 and PHP 8.3, including repeat-build hash comparison.

### Architecture

- No Core database tables in 1.0.0.
- No product-specific business logic.
- No global plugin listeners or automatic per-request work.
- Cross-product relations remain storage-agnostic until real consumers prove a common persistence requirement.
- `VERSION` is the release source of truth and must match library, plugin and package manifests.
