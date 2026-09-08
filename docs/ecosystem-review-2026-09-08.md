# Xdecaro ecosystem review — 2026-09-08

This is the repository-level cross-component review requested before any Core 1.2.0 expansion.

It records what has actually been verified in source/build automation and what still requires a real Joomla runtime. A green GitHub workflow is not treated as proof of a successful Joomla installation or browser/runtime test.

## Scope

Current repositories/components reviewed:

| Product | Technical identity | Core integration status | Repository/build status |
| --- | --- | --- | --- |
| Core | `pkg_xdecarocore`, `lib_xdecarocore`, `plg_system_xdecarocore` | Provider | CI green |
| Forms | `pkg_decaroforms`, `com_decaroforms` | Core shared UI/fallback integrated | main build green; separate Builder migration work exists |
| Courses | `pkg_decarocourses`, `com_decarocourses` | Core shared UI/fallback integrated | main workflow green |
| Competitions | `pkg_decarodcl`, `com_decarodcl` | Core shared UI/reference diagnostics integrated | current release workflow green |
| Documents | `pkg_decarodocuments`, `com_decarodocuments` | Core-first; document domain remains local | release workflow green |
| Membership | `pkg_decaromembership`, `com_decaromembership` | Core shared UI/fallback integrated | CI green |
| Events | `pkg_decaroevents`, `com_decaroevents` | Core-first; event domain remains local | release workflow green |
| Editor | `pkg_decaroeditor`, `com_decaroeditor` | Core 1.1 UI optional with fallback; Editor engine remains local | main alpha3 workflow green; separate Builder Engine work exists |
| Finance | `pkg_decarofinance`, `com_decarofinance` | Core 1.1 UI/references optional with fallback | 1.0.0 release/build green |
| Protocol | `pkg_decaroprotocol`, `com_decaroprotocol` | Core 1.1 UI/references optional with fallback | current main build green |
| Draw | `pkg_decarodraw`, `com_decarodraw` | Core 1.1 UI/references optional with fallback | 0.1.0 prerelease/build green |
| Organizations | `pkg_decaroorganizations`, `com_decaroorganizations` | Core 1.1+ hard dependency; public organization reference | 0.1.0 build/release baseline |
| People | `pkg_decaropeople`, `com_decaropeople` | Core 1.1+ hard dependency; public person reference | 0.1.0 build/release baseline |
| Inventory | `pkg_decaroinventory`, `com_decaroinventory` | Core 1.1+ hard dependency; public item reference | 0.1.0 build/release baseline |
| Resources | `pkg_decaroresources`, `com_decaroresources` | Core 1.1+ hard dependency; public resource reference | 0.1.0 build/release baseline |

Communication and Bookings are part of the mandatory consumer standard but no repository is currently available to implement or test them. They must start Core-first when their repositories are created.

## Architecture findings

The required dependency direction remains:

`Product -> Core`

No product-specific dependency is to be added from Core back into Forms, Courses, Competitions, Documents, Membership, Events, Editor, Finance, Protocol, Draw, Organizations, People, Inventory, Resources or future products.

Cross-product identity uses public `EntityReference` / `RelationReference` contracts when available. The component owning an entity remains responsible for ACL and domain validation. A reference never grants authorization.

Direct reads/writes of another product's private tables are not an accepted integration mechanism. In particular Draw must not write Competitions tables, Finance must not infer Competitions rules from DCL tables, Organizations must not use People/Membership private tables, Inventory must not use Resources/Bookings private tables, and Resources must not become a hidden booking system.

Shared UI is opt-in through Core Web Asset Manager assets / `AssetService` and `.xdecaro-scope`. Product-specific UI behavior remains in its owner component.

### New domain separations

- **Organizations** owns organization/legal-entity master records and hierarchy. It may reference People, Membership, Finance and other products but does not own their workflows.
- **People** owns reusable person master data. A person record is not a Joomla User account and is not a Membership record. People should minimize stored personal data and never expose person data through diagnostics.
- **Inventory** owns physical item catalog, stock quantities and inventory movements. Movement history is domain data and must not be silently rewritten during ordinary updates.
- **Resources** owns reusable allocatable resource definitions and capacity/availability-oriented metadata. It is deliberately separate from Inventory stock and from Bookings reservation state.

## Release/distribution finding

Finance, Protocol and Draw repositories are currently private. Their package manifests/update feeds can reference GitHub URLs for development, but a normal unauthenticated Joomla installation cannot depend on private GitHub raw/release URLs as a production update channel.

Organizations, People, Inventory and Resources repositories are public, so their GitHub Release/update-feed model can be used by Joomla once the first releases are published and checksum-verified.

Before production auto-update is claimed for a private product, choose one of these approaches:

- make the distribution repository/assets publicly reachable; or
- publish signed/checksummed packages and update XML through a separate public distribution endpoint that does not expose private source; or
- implement an authenticated update mechanism intentionally.

Do not claim automatic Joomla updates for private GitHub assets until this is resolved.

## Repository-level checks completed

The current review covers, where implemented by each repository's workflow:

- PHP syntax validation;
- XML/manifest validation;
- version/package coherence checks;
- deterministic/repeatable packaging where supported;
- ZIP integrity checks;
- Core integration/fallback or hard-dependency smoke checks;
- schema safety guards against destructive normal updates;
- private-table coupling guards in the new baselines;
- release/checksum consistency for products with completed release workflows.

Organizations, People, Inventory and Resources were created from empty repositories as first Core-first 0.1.0 baselines. Their CI runs validate PHP 8.1/8.3, XML, deterministic packaging, ZIP integrity and cross-product table boundaries.

## Runtime Joomla matrix still required

Do not mark the ecosystem fully production-validated until the following is exercised on real Joomla installations:

1. clean package installation;
2. supported upgrade path without data/configuration loss;
3. Core installed and detected correctly;
4. optional-Core fallback where the product allows Core to be absent;
5. hard Core dependency failure message where Core is required;
6. administrator and frontend views affected by the integration;
7. server-side ACL denial and permitted actions;
8. CSRF protection on every state-changing action;
9. database create/update/schema state, including foreign-key behavior where used;
10. browser console with no JavaScript errors;
11. PHP error log with no warnings/fatals;
12. desktop/tablet/smartphone rendering;
13. light/dark rendering;
14. real cross-product integration paths without private-table coupling.

Claims for Joomla 4/5/6 must match the versions actually exercised. A source-level target declaration alone is not a compatibility test. The new Organizations/People/Inventory/Resources baselines intentionally declare Joomla 5/6 only until runtime compatibility is exercised.

## Current blockers before Core 1.2.0

1. Communication repository/component does not yet exist.
2. Bookings repository/component does not yet exist.
3. Real Joomla runtime matrix above is still outstanding.
4. Private-product update distribution must be resolved before production auto-update claims.
5. Open Builder-related work in Forms/Editor is separate feature work and must not be merged merely as part of the Core review.
6. Organizations, People, Inventory and Resources are first technical baselines, not yet feature-complete domain products; their public API surface must remain deliberately small until real consumer requirements emerge.

## Core 1.2.0 gate

Do not add a new public Core API simply because one product wants it. A candidate may enter Core 1.2.0 only when at least two real consumers demonstrate the same domain-neutral requirement and the API can remain stable independently of those product domains.
