# Xdecaro ecosystem runtime validation matrix

This matrix is the stabilization gate for the Core `1.4.x` line and for functional cross-product integrations.

Repository CI, deterministic ZIP generation and successful GitHub Releases are necessary but are not sufficient proof of Joomla runtime compatibility. Runtime claims must be backed by an actual Joomla installation.

## Automated runtime baseline

`.github/workflows/runtime-smoke.yml` installs real Joomla instances with MariaDB and exercises released packages through Joomla CLI.

The stabilization baseline is deliberately pinned so that a new Joomla patch release cannot change the result of an already-defined gate:

- Joomla `5.4.8` with PHP `8.1`;
- Joomla `6.1.3` with PHP `8.3`;
- Editor only on Joomla 6, matching its current manifest/runtime target.

The Joomla ZIPs and Core release ZIPs are SHA-256 verified before installation.

Released packages under the runtime gate:

- Core `1.4.0`;
- Courses `1.3.0`;
- Forms `1.6.0`;
- Competitions `1.1.0`;
- Documents `1.2.0`;
- Membership `1.2.0`;
- Events `1.1.1`;
- Editor `0.1.0-alpha5`;
- Finance `1.1.0`;
- Protocol `1.3.0`.

Notifications is not duplicated in this central package matrix because its own repository CI already performs clean package installation on Joomla `4.4.14`, `5.4.8` and `6.1.3`. It remains part of the ecosystem namespace audit.

The automated gate verifies:

1. clean Joomla installation;
2. package ZIP integrity;
3. package installation using Joomla CLI;
4. optional consumers can install before Core;
5. hard Core dependencies reject installation before Core and install after Core;
6. canonical `xdecaro\Core\Version` autoloading after package installation;
7. Core `1.4.0+` availability;
8. `xdecaro\Core\Integration\CapabilityRegistry` availability;
9. Core upgrade from `1.3.0` to `1.4.0` on Joomla 5.4.8 and 6.1.3.

Protocol additionally owns a repository-level integration gate that installs Core `1.4.0`, Documents `1.2.0` and Protocol `1.3.0` together on Joomla 5.4.8 and 6.1.3, verifies the installed schemas and rejects direct Protocol access to `#__decarodocuments_*`.

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
- `AssetService` calls remain idempotent;
- `EntityReference`, `RelationReference`, `IntegrationEvent`, `Capability` and `CapabilityRegistry` stay additive and domain-neutral.

### Documents
- mandatory Core preflight remains atomic;
- private storage creation, MIME/extension validation and download ACL are tested;
- public relation API owns relation persistence and never exposes private storage paths.

### Events
- mandatory Core preflight remains atomic;
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
- Protocol checks its own ACL before delegating;
- Documents keeps document ACL and relation persistence ownership;
- Protocol never reads or writes `#__decarodocuments_*` directly.

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
