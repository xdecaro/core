# Ecosystem Package Naming Standard

**Date:** 2026-09-17  
**Status:** Proposed global standard  
**Owner:** xdecaro Joomla ecosystem

## Goal

Standardize the Joomla **package layer** across the xdecaro ecosystem so every product uses a clean package identifier without the historical `xdecaro` / `decaro` prefix, while preserving existing component, plugin, module, library, namespace and database identifiers unless a separate migration is explicitly designed.

This change is intentionally limited to Joomla package identity, release artifacts and update metadata.

## Canonical package naming rule

For a product named `<product>` in lowercase ASCII:

- Joomla package extension element: `pkg_<product>`
- `<packagename>` value: `<product>`
- source manifest: `package/pkg_<product>.xml`
- release ZIP: `pkg_<product>_<version>.zip`
- Joomla update feed: `updates/pkg_<product>.xml`
- update server display name: `<Product> Updates`
- GitHub Release asset: `pkg_<product>_<version>.zip`
- checksum entries and release workflow references must use the same canonical filename

Examples:

- `pkg_core`
- `pkg_people`
- `pkg_organizations`
- `pkg_competitions`
- `pkg_membership`

## Scope boundary

The package rename does **not** automatically rename child extensions or runtime contracts.

Unless a separate migration is approved, keep unchanged:

- `com_*` component identifiers;
- `plg_*` plugin identifiers and plugin groups;
- `mod_*` module identifiers;
- `lib_*` library identifiers;
- PHP namespaces;
- service identifiers;
- Web Asset identifiers;
- database table names;
- ACL action names;
- public provider/service APIs;
- stored UUIDs and cross-product entity references.

Examples that remain valid after package normalization:

- People may remain `com_xdecaropeople` inside `pkg_people`.
- Organizations may remain `com_xdecaroorganizations` inside `pkg_organizations`.
- Competitions may keep `com_competitions` and its current plugins/modules inside `pkg_competitions`.
- Core may keep `lib_xdecarocore`, `com_xdecarocore` and `plg_system_xdecarocore` inside `pkg_core`.

## Product mapping

| Repository | Legacy package | Canonical package |
| --- | --- | --- |
| `core` | `pkg_xdecarocore` | `pkg_core` |
| `people` | `pkg_xdecaropeople` | `pkg_people` |
| `organizations` | `pkg_xdecaroorganizations` | `pkg_organizations` |
| `competitions` | `pkg_xdecarocompetitions` | `pkg_competitions` |
| `resources` | `pkg_xdecaroresources` | `pkg_resources` |
| `Inventory` | `pkg_xdecaroinventory` | `pkg_inventory` |
| `notifications` | `pkg_xdecaronotifications` | `pkg_notifications` |
| `analytics` | `pkg_xdecaroanalytics` | `pkg_analytics` |
| `tasks` | `pkg_xdecarotasks` | `pkg_tasks` |
| `feedback` | `pkg_xdecarofeedback` | `pkg_feedback` |
| `draw` | `pkg_xdecarodraw` | `pkg_draw` |
| `courses` | `pkg_decarocourses` | `pkg_courses` |
| `documents` | `pkg_decarodocuments` | `pkg_documents` |
| `membership` | `pkg_decaromembership` | `pkg_membership` |
| `events` | `pkg_decaroevents` | `pkg_events` |
| `editor` | `pkg_decaroeditor` | `pkg_editor` |
| `finance` | `pkg_decarofinance` | `pkg_finance` |
| `protocol` | `pkg_decaroprotocol` | `pkg_protocol` |

`forms` is excluded from this migration until its actual packaging structure is established. It must adopt this standard if/when it ships as a Joomla package.

## Joomla updater standard

Every canonical package must participate in Joomla's normal extension update flow.

Each package manifest must contain one extension update server pointing to the repository's canonical feed:

`https://raw.githubusercontent.com/xdecaro/<repo>/main/updates/pkg_<product>.xml`

The feed must identify the canonical package and publish:

- package element/name matching the canonical package identity;
- current version;
- full ZIP download URL for `pkg_<product>_<version>.zip`;
- SHA-256 checksum;
- supported Joomla target platform;
- PHP minimum where applicable;
- stable tag.

Release CI must verify that the published feed resolves to the newly published package and that the downloaded ZIP matches the expected SHA-256.

The product policy is **automatic detection, manual one-click installation in Joomla**. Silent unattended extension installation is outside scope.

## Migration from legacy package identities

The current site is a test environment, so the ecosystem may migrate package identities now rather than preserve historical branding indefinitely. However the migration must still preserve child extensions and product data.

For each repository:

1. build and install the canonical package over a Joomla test installation containing the legacy package;
2. verify that every child extension remains installed and functional;
3. verify package-child ownership/associations in Joomla after installation;
4. verify that no duplicate functional child extension is created;
5. verify that product tables, configuration and content are unchanged;
6. remove or retire the legacy package registration only after the canonical package is proven to own the expected child extensions;
7. verify Joomla update discovery using the canonical update feed;
8. verify update from that canonical version to a later test version.

No migration may uninstall a legacy package in a way that removes its child extensions before the canonical package has safely taken ownership.

If Joomla requires a compatibility step, installer scripts may temporarily recognize both the legacy and canonical package identifiers. That compatibility path must be removed only after all active test installations have migrated.

## Dependency checks

Package identity must not be treated as the only proof that a dependency is available.

Where a product checks for Core or another package today, migration code must:

- prefer the real public runtime API/class/capability when available;
- recognize `pkg_core` as the canonical package identity;
- temporarily recognize `pkg_xdecarocore` during the migration window if required;
- never query another product's private tables to establish dependency availability.

The same rule applies to any future package-to-package dependency checks.

## Repository changes required during migration

A repository adopting the standard must update all package-layer references together:

- package manifest filename and `<packagename>`;
- package installer script class name where tied to package identity;
- build script output filenames;
- release workflow asset names and notes;
- deterministic build/checksum assertions;
- update feed filename/content;
- manifest `<updateservers>` URL;
- tests and CI fixtures referring to the legacy package;
- README/release documentation that exposes the technical package name;
- cross-repository runtime test download URLs when they consume the renamed package.

A repository must not ship a half-migrated state where the manifest, ZIP, update feed and release workflow disagree on the package identity.

## Rollout order

Migration should follow dependency order rather than alphabetical order:

1. **Core** — establish `pkg_core` and migration compatibility.
2. **People** — establish `pkg_people`; add the limited identity-detail provider permission required by Organizations.
3. **Organizations** — establish `pkg_organizations`; consume People identity details for member disambiguation.
4. **Shared infrastructure products** — Notifications, Analytics, Tasks, Resources.
5. **Business products** — Competitions, Courses, Documents, Membership, Events, Finance, Protocol, Inventory, Feedback, Draw, Editor.
6. **Forms** — only after its package architecture is defined.

Each repository remains independently releasable and must be green before the next dependency-sensitive migration depends on it.

## People / Organizations identity-detail requirement

As part of the first rollout wave, People will expose a permission narrower than `people.view_sensitive` for identity disambiguation. The public People provider may return only the approved identity-detail subset needed by consumers, initially:

- `birth_date`;
- `birth_place`.

Organizations may display these values in person search results to distinguish homonyms but must not copy them into appointment storage.

Full sensitive fields such as disability, tax identifier and residence remain protected by the existing sensitive-data permission and are not exposed by this limited contract.

## Validation requirements

For every migrated package, automated and/or runtime validation must cover:

- clean install on its supported Joomla version;
- install over the previous legacy-package test state;
- preservation of child extensions;
- preservation of product data/configuration;
- deterministic build;
- ZIP integrity;
- SHA-256 correctness;
- Joomla update feed correctness;
- successful update discovery;
- successful update installation to a later canonical test version;
- dependency checks against Core/public APIs;
- no direct cross-product table access introduced by the migration.

## Non-goals

This standard does not authorize a global rename of `com_*`, `plg_*`, `mod_*`, `lib_*`, PHP namespaces, database tables or public APIs.

Those identifiers may be normalized later only through separate product-specific designs with explicit migration paths.
