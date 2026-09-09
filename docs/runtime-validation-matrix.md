# Xdecaro ecosystem runtime validation matrix

This matrix is the stabilization gate for the Core `1.4.x` line and for functional cross-product integrations.

Repository CI, deterministic ZIP generation and successful publication are necessary but are not sufficient proof of Joomla runtime compatibility. Runtime claims must be backed by an actual Joomla installation.

## Automated runtime baseline

`.github/workflows/runtime-smoke.yml` installs real Joomla instances with MariaDB and exercises distributed packages through Joomla CLI.

The stabilization baseline is deliberately pinned so that a new Joomla patch release cannot change the result of an already-defined gate:

- Joomla `5.4.8` with PHP `8.1` for products whose current distribution declares Joomla 5 support;
- Joomla `6.1.3` with PHP `8.3` for all applicable products;
- Courses, Forms, Competitions and Editor are tested only on Joomla 6 because their current distributed metadata/manifests target Joomla 6 / PHP 8.3.

Joomla ZIPs and all product package ZIPs are SHA-256 verified before installation. The gate follows the real distribution channel of each product rather than assuming every package is a GitHub Release asset:

- Courses and Forms are downloaded from their versioned repository `releases/<version>/` paths, matching their Joomla update feeds;
- Core, Competitions, Documents, Membership, Events, Editor, Finance and Protocol are downloaded from their published GitHub Release assets;
- package checksums are pinned to the actual distributed artifact or its authoritative Joomla update feed.

Distributed packages under the runtime gate:

- Core `1.4.0`;
- Courses `1.3.0`;
- Forms `1.6.0`;
- Competitions `1.1.0`;
- Documents `1.2.2`;
- Membership `1.2.0`;
- Events `1.1.2`;
- Editor `0.1.0-alpha5`;
- Finance `1.1.0`;
- Protocol `1.4.0`.

Notifications is not duplicated in this central package matrix because its own repository CI already performs clean package installation on Joomla `4.4.14`, `5.4.8` and `6.1.3`. It remains part of the ecosystem namespace audit.

The automated gate verifies:

1. clean Joomla installation;
2. package ZIP integrity and authoritative SHA-256;
3. package installation using Joomla CLI;
4. optional consumers are genuinely registered before Core is installed;
5. hard Core dependencies leave neither package/component registration nor component files behind before Core, then install after Core;
6. canonical `xdecaro\Core\Version` autoloading through Joomla's real extension namespace map;
7. Core `1.4.0` availability;
8. `xdecaro\Core\Integration\CapabilityRegistry` availability;
9. Core upgrade from the actually published `1.3.0` package to `1.4.0` on Joomla 5.4.8 and 6.1.3, including manifest-cache and runtime-autoload verification.

The hard-dependency assertion deliberately does not trust only the exit status of `extension:install`: Joomla CLI can report command success while an extension installer script has rejected the package. The gate checks `#__extensions` and the component filesystem directly to detect partial installation.

Documents additionally owns repository-level clean-install, `1.2.0 -> 1.2.2` repair and missing-Core rejection tests on Joomla 5.4.8 and 6.1.3. These tests verify the Joomla SQL manifest repair, preserve the non-destructive `CREATE TABLE IF NOT EXISTS` recovery path and prove that the mandatory Core preflight leaves no package/component registration or component files behind.

Events additionally owns repository-level clean-install, `1.1.1 -> 1.1.2` repair and missing-Core rejection tests on Joomla 5.4.8 and 6.1.3. These tests verify the corrected package installer class, Joomla SQL manifest compatibility and non-destructive repair of Events-owned tables.

Protocol additionally owns a repository-level functional integration gate that installs Core `1.4.0`, Documents `1.2.2` and Protocol `1.4.0` together on Joomla 5.4.8 and 6.1.3. It verifies clean installation and repair from published Protocol `1.2.0`, validates the installed schemas, rejects direct Protocol access to `#__decarodocuments_*`, and exercises the provider-owned Documents relation API end to end: attach and read on a draft record, protocol finalization, then server-side rejection of further attach/detach operations. Protocol enforces Documents `1.2.1+` for this optional integration.

This is a runtime smoke gate, not a replacement for browser/UI/security regression testing.

## Core dependency policy

Current matrix:

| Product | Core policy | Runtime minimum for Core-backed features |
| --- | --- | --- |
| Core | self | 1.4.0 |
| Courses | optional | 1.3.0 |
| Forms | optional | 1.3.0 |
| Competitions | optional/integration boundary | canonical Core contracts |
| Documents | mandatory | 1.3.0 |
| Membership | optional | 1.3.0 |
| Events | mandatory | 1.3.0 |
| Editor | optional | 1.3.0 |
| Finance | optional | 1.3.0 |
| Protocol | optional | 1.3.0 |

Core `1.4.0` remains compatible with consumers requiring Core `1.3.0+` because the Capability Registry is additive.

## Manual runtime checks still required

Record PASS / FAIL / N/A with Joomla version, PHP version, product version and date.

| Check | Required result |
| --- | --- |
| Clean installation | Package installs without warnings/fatals and registers expected extensions only |
| Upgrade | Existing data/configuration survive a supported upgrade |
| Uninstall | Follows the product's documented preservation/deletion policy |
| Core detection | Correct Core version and API availability are reported |
| Missing Core | Optional consumers fall back cleanly; hard dependencies fail before partial installation |
| Administrator access | `core.manage` and product ACL are enforced server-side |
| State-changing requests | Joomla CSRF validation is enforced |
| Input validation | Invalid IDs/states/values are rejected server-side |
| Database | Tables/indexes/update state remain coherent; no destructive normal update |
| Cross-product boundary | No direct reads/writes of another product's private tables |
| Web assets | Core assets load through WAM/AssetService only when requested |
| Shared UI scope | Core primitives remain inside `.xdecaro-scope` |
| JavaScript | No relevant console errors, duplicate listeners or duplicate AJAX |
| PHP runtime | No relevant warnings/deprecations/fatals |
| Desktop/tablet/smartphone | Primary affected views remain usable |
| Light/dark mode | No contrast or hard-coded surface regressions |
| Accessibility basics | Labels, focus and semantic controls remain usable |

## Product-specific focus

### Core
- package installs library, legacy compatibility library and system plugin;
- canonical `xdecaro\Core` source remains authoritative;
- `Xdecaro\Core` exists only through the compatibility library;
- runtime smoke creates Joomla's extension namespace map before probing Core classes, matching the CMS lifecycle;
- `AssetService` calls remain idempotent;
- `EntityReference`, `RelationReference`, `IntegrationEvent`, `Capability` and `CapabilityRegistry` stay additive and domain-neutral.

### Documents
- mandatory Core preflight remains atomic and is runtime-tested with Core absent;
- private storage creation, MIME/extension validation and download ACL are tested;
- public relation API owns relation persistence and never exposes private storage paths;
- Joomla SQL manifest uses `charset="utf8"` while table definitions remain `utf8mb4`;
- the 1.2.1 repair for affected 1.2.0 installations remains non-destructive in 1.2.2; 1.2.2 additionally fixes Joomla discovery of the mandatory-Core package installer.

### Events
- mandatory Core preflight remains atomic and is runtime-tested with Core absent;
- Joomla SQL manifest uses `charset="utf8"` while table definitions remain `utf8mb4`;
- 1.1.2 repairs affected prior installations using only `CREATE TABLE IF NOT EXISTS` for Events-owned tables;
- capacity/waitlist/check-in changes preserve ACL and CSRF.

### Editor
- Core remains optional;
- Joomla editor plugin/canvas/media/history continue to work without Core;
- shared Core assets do not absorb editor behavior.

### Finance
- ledger/deposit/payment writes retain transaction/idempotency protections;
- no source product private-table dependency.

### Protocol
- register numbering remains atomic and immutable after assignment;
- Documents integration remains optional and public-contract based;
- Protocol `1.4.0` exposes linked Documents in the record editor without transferring document ownership into Protocol;
- saved draft records may attach/detach existing Documents records; protocolled records keep linked documents read-only;
- mutation tasks use Joomla CSRF validation and server-side Protocol state/ACL checks;
- Documents keeps document ACL, storage and relation persistence ownership;
- Documents `1.2.1+` is enforced before enabling the integration;
- Protocol never reads or writes `#__decarodocuments_*` directly;
- Protocol 1.4.0 retains the non-destructive repair path for affected older Protocol installations.

## Namespace audit

`.github/workflows/ecosystem-audit.yml` checks runtime PHP in current public xdecaro repositories and rejects new `Xdecaro\Core` consumption. Core itself is checked separately: canonical sources must use `xdecaro\Core`; the legacy manifest is the only compatibility boundary.

`xdecaro/draw` is private, so the Core repository token cannot perform cross-repository checkout for it. Draw remains covered by its own CI, which rejects `Xdecaro\` runtime usage and cross-component private-table coupling. It is intentionally excluded only from the central checkout matrix, not from the architectural rule.

## Stabilization rule

Core is currently frozen on the `1.4.x` minor line. During this gate:

- compatible fixes use PATCH releases (`1.4.1`, `1.4.2`, ...);
- no `1.5.0` is published merely to add a product-specific integration;
- a new Core public primitive requires evidence from at least two real products and must remain domain-neutral;
- provider-specific capability names and operations stay owned by their products.

## Completion rule

The stabilization gate is considered satisfied when:

1. namespace audit is green;
2. automated Joomla runtime smoke is green for applicable products/majors;
3. failures affecting shared Core contracts are corrected on the `1.4.x` line;
4. browser/security checks are recorded for affected production paths;
5. functional integrations call provider-owned public services and never another component's private tables.
