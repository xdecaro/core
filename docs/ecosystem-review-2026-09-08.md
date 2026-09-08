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

Communication and Bookings are part of the mandatory consumer standard but no repository is currently available to implement or test them. They must start Core-first when their repositories are created.

## Architecture findings

The required dependency direction remains:

`Product -> Core`

No product-specific dependency is to be added from Core back into Forms, Courses, Competitions, Documents, Membership, Events, Editor, Finance, Protocol, Draw or future products.

Cross-product identity uses public `EntityReference` / `RelationReference` contracts when available. The component owning an entity remains responsible for ACL and domain validation. A reference never grants authorization.

Direct reads/writes of another product's private tables are not an accepted integration mechanism. In particular Draw must not write Competitions tables, Finance must not infer Competitions rules from DCL tables, and Protocol/Documents integrations must go through stable public boundaries.

Shared UI is opt-in through Core Web Asset Manager assets / `AssetService` and `.xdecaro-scope`. Product-specific UI behavior remains in its owner component.

## Release/distribution finding

Finance, Protocol and Draw repositories are currently private. Their package manifests/update feeds can reference GitHub URLs for development, but a normal unauthenticated Joomla installation cannot depend on private GitHub raw/release URLs as a production update channel.

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
- Core integration/fallback smoke checks;
- schema safety guards against destructive normal updates;
- release/checksum consistency for products with completed release workflows.

Finance packaging was hardened to deterministic ZIP creation before 1.0.0 release. Draw packaging was likewise hardened before 0.1.0 prerelease.

## Runtime Joomla matrix still required

Do not mark the ecosystem fully production-validated until the following is exercised on real Joomla installations:

1. clean package installation;
2. supported upgrade path without data/configuration loss;
3. Core installed and detected correctly;
4. optional-Core fallback where the product allows Core to be absent;
5. administrator and frontend views affected by the integration;
6. server-side ACL denial and permitted actions;
7. CSRF protection on every state-changing action;
8. database create/update/schema state;
9. browser console with no JavaScript errors;
10. PHP error log with no warnings/fatals;
11. desktop/tablet/smartphone rendering;
12. light/dark rendering;
13. real cross-product integration paths without private-table coupling.

Claims for Joomla 4/5/6 must match the versions actually exercised. A source-level target declaration alone is not a compatibility test.

## Current blockers before Core 1.2.0

1. Communication repository/component does not yet exist.
2. Bookings repository/component does not yet exist.
3. Real Joomla runtime matrix above is still outstanding.
4. Private-product update distribution must be resolved before production auto-update claims.
5. Open Builder-related work in Forms/Editor is separate feature work and must not be merged merely as part of the Core review.

## Core 1.2.0 gate

Do not add a new public Core API simply because one product wants it. A candidate may enter Core 1.2.0 only when at least two real consumers demonstrate the same domain-neutral requirement and the API can remain stable independently of those product domains.
