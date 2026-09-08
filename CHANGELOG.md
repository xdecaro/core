# Changelog

All notable changes to Xdecaro Core are documented here.

The project follows Semantic Versioning.

## 1.0.0 — 2026-09-08

### Added

- Initial `pkg_xdecarocore` package structure.
- Reusable `lib_xdecarocore` Joomla library.
- Lightweight `plg_system_xdecarocore` system plugin bootstrap.
- `Xdecaro\Core\Version` public version contract.
- `Xdecaro\Core\Integration\EntityReference` for stable cross-component entity references.
- `Xdecaro\Core\Integration\RelationReference` for typed cross-component relations.
- Cross-product integration contract documentation.
- Reproducible ZIP build script.

### Architecture

- No Core database tables in 1.0.0.
- No product-specific business logic.
- No global plugin listeners or automatic per-request work.
- Cross-product relations remain storage-agnostic until real consumers prove a common persistence requirement.
