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
