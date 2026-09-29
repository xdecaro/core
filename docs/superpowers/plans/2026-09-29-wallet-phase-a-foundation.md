# Xdecaro Wallet Phase A — Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver Wallet `0.1.0`: an installable foundation with Membership as the first source, stable source UUIDs, provider-neutral pass storage, secure QR verification, basic administrator UI, and optional/failure-isolated integration.

**Architecture:** Membership remains authoritative for cards and business validity. Wallet stores only digital-pass technical state, provider-neutral rendering snapshots, opaque verification tokens and diagnostics. Membership integrates through an optional `xdecarowallet` provider plugin plus a conditional integration service; Wallet never reads Membership tables directly.

**Tech Stack:** PHP 8.x, Joomla MVC/service providers/plugins/events/ACL/CSRF, Joomla Database API, MySQL/MariaDB using `#__`, Xdecaro Core shared UI primitives where already available, Web Asset Manager.

**Spec:** `docs/superpowers/specs/2026-09-29-wallet-architecture-design.md`

## Global Constraints

- Membership cards stay in Membership; Wallet never becomes the source of membership validity.
- Wallet is optional: Membership must work when Wallet is absent, disabled or misconfigured.
- No Wallet-specific API is added to Core in Phase A.
- QR tokens contain no personal data; only SHA-256 token hashes are stored.
- Wallet/provider failure never rolls back an already successful Membership transaction.
- Use Joomla ACL, CSRF, filtered input, escaped output and bound queries.
- Wallet Phase A version is exactly `0.1.0`; Phase B will use `0.2.0`, Phase C `0.3.0`, and only the final stabilization phase may publish `1.0.0`.
- Never distribute different code with the same version number.
- The live Membership repository currently reports `VERSION` 1.9.33; before editing, reconcile it with any newer tested/released Membership source so later work is never overwritten.
- Joomla 6.x is the primary runtime baseline; avoid unnecessary incompatibilities with Joomla 4/5 where cleanly possible.

## Review Focus

- Existing Membership cards without UUID: backfill all rows before enforcing unique/not-null.
- Wallet missing or disabled: Membership card save still succeeds with no fatal lookup.
- Repeated sync of one card: one Wallet pass only, no duplicate token/provider records.
- Invalid/revoked/malformed token or missing source: fail closed and expose no diagnostics.
- Membership temporarily unavailable: verification returns `unavailable`, never cached `valid`.

---

### Task 1: Reconcile Membership baseline and add stable card UUIDs

**Files:**
- Inspect/modify: `membership/VERSION`
- Modify: `membership/component/admin/sql/install.mysql.utf8mb4.sql`
- Create: `membership/component/admin/sql/updates/mysql/<next-membership-version>.sql`
- Modify: `membership/component/admin/src/Config/CaseEntities.php`
- Create: `membership/component/admin/src/Service/CardIdentityService.php`
- Modify: `membership/component/admin/src/Model/RecordModel.php`
- Modify release metadata required by the existing Membership build process
- Create: `membership/tests/card-uuid-contract.php`
- Create: `membership/tests/card-uuid-runtime.php`

**Interfaces:**
- `CardIdentityService::generate(): string`
- `#__decaromembership_cards.uuid CHAR(36)` immutable and unique (`uq_card_uuid`)

- [ ] Reconcile `membership/main`, release metadata and the newest tested source before changing code; all version metadata must agree.
- [ ] Write `card-uuid-contract.php` asserting fresh-install schema, safe migration order and non-editability of UUID.
- [ ] Run it and confirm failure before implementation.
- [ ] Fresh install: add non-null UUID plus unique index.
- [ ] Update migration: add nullable UUID -> backfill every missing value -> verify no null/empty/duplicates -> enforce non-null and unique index.
- [ ] Implement RFC 4122-style lowercase v4 UUID generation with `random_bytes(16)` and correct version/variant bits; no new dependency.
- [ ] On new card save assign UUID once; on edits preserve stored UUID and never accept a user-supplied replacement.
- [ ] Runtime test: different new cards get different UUIDs; edits/migrations preserve identity.
- [ ] Run existing Membership card/DCL/lifecycle/install/update/package regression tests.
- [ ] Commit `feat: add stable membership card UUIDs`.

---

### Task 2: Add Membership Wallet source/integration services and provider plugin

**Files:**
- Create: `membership/component/admin/src/Service/WalletSourceService.php`
- Create: `membership/component/admin/src/Service/WalletIntegrationService.php`
- Modify: `membership/component/admin/src/Extension/MembershipComponent.php`
- Create: `membership/plugins/xdecarowallet/decaromembership/decaromembership.xml`
- Create: `membership/plugins/xdecarowallet/decaromembership/services/provider.php`
- Create: `membership/plugins/xdecarowallet/decaromembership/src/Extension/Decaromembership.php`
- Create: `membership/plugins/xdecarowallet/decaromembership/src/Provider/MembershipProvider.php`
- Create plugin language files IT/EN/FR
- Modify Membership package/build to include the plugin
- Create: `membership/tests/wallet-source-contract.php`
- Create: `membership/tests/wallet-optional-runtime.php`

**Interfaces:**
- `WalletSourceService::getPassPayload(string $sourceUuid): ?array`
- `WalletSourceService::validateCard(string $sourceUuid): array`
- `WalletIntegrationService::isAvailable(): bool`
- `WalletIntegrationService::syncCard(string $sourceUuid): void`

- [ ] Write payload tests permitting only: `pass_type`, `source_uuid`, `person_uuid`, `issuer_organization_uuid`, `display_name`, `card_number`, `category_label`, `valid_from`, `expires_at`, `display_status`, optional `photo_reference`, `issuer_name`, `issuer_logo_reference`, `locale`.
- [ ] Explicitly reject tax code, payments, internal notes and document data from Wallet payloads.
- [ ] Implement `WalletSourceService` using Membership-owned repositories/integrations; missing/unpublished source returns `null`.
- [ ] `validateCard()` derives live status/dates from Membership and returns one of `valid`, `invalid`, `expired`, `suspended`, `revoked`, `unavailable` plus minimal public fields.
- [ ] Expose the source service from `MembershipComponent` following existing cross-product service patterns.
- [ ] Implement `WalletIntegrationService` as a soft dependency: conditionally boot `com_xdecarowallet`, never import/type-hint Wallet classes at Membership file-load time, catch/log sanitized failures.
- [ ] Add `plg_xdecarowallet_decaromembership`, mirroring the existing `xdecaroanalytics` provider registration pattern; it registers only when Wallet imports the custom plugin group.
- [ ] Test Membership with Wallet completely absent; card saves must still persist normally.
- [ ] Commit `feat: expose membership cards to Wallet`.

---

### Task 3: Create Wallet repository/package/component `0.1.0` and schema

**Files in new repository `xdecaro/wallet`:**
- `AGENTS.md`, `README.md`, `CHANGELOG.md`, `VERSION`, `.gitignore`
- `component/xdecarowallet.xml`
- `component/admin/access.xml`, `config.xml`, `services/provider.php`
- `component/admin/src/Extension/XdecarowalletComponent.php`
- `component/admin/sql/install.mysql.utf8mb4.sql`
- `component/admin/sql/uninstall.mysql.utf8mb4.sql`
- `component/admin/sql/updates/mysql/0.1.0.sql`
- admin/site language files IT/EN/FR
- `package/pkg_xdecarowallet/pkg_xdecarowallet.xml`
- `package/pkg_xdecarowallet/script.php`
- build/update-server files following current Xdecaro convention
- tests: `schema-contract.php`, `package-contract.php`, `language-contract.php`

**Interfaces:**
- Component `com_xdecarowallet`
- Package `pkg_xdecarowallet`
- Development/release artifact for this phase: `pkg_xdecarowallet_0.1.0.zip`
- Tables: `#__xdecarowallet_passes`, `#__xdecarowallet_provider_items`, `#__xdecarowallet_verification_tokens`, `#__xdecarowallet_verification_events`

- [ ] Create repository `xdecaro/wallet` or, if repository creation is unavailable to the active connector, an isolated local source tree; never place Wallet product code inside Core/Membership.
- [ ] Write schema/package/language tests before implementation.
- [ ] Implement passes unique key `(source_component, source_type, source_uuid, pass_type)`.
- [ ] Implement provider-items unique key `(pass_uuid, provider)`.
- [ ] Verification tokens store unique `token_hash CHAR(64)` only, never raw token.
- [ ] Verification events require no personal-data columns.
- [ ] Add ACL for view/manage, resync, token rotate/revoke, diagnostics and configuration.
- [ ] Register component/services with Joomla DI conventions.
- [ ] Build package and verify every manifest/version/update file reports `0.1.0` and ZIP has no extra parent folder/source junk.
- [ ] Commit `feat: bootstrap Xdecaro Wallet component`.

---

### Task 4: Implement source registration and idempotent pass synchronization

**Files:**
- `wallet/component/admin/src/Contract/SourceAdapterInterface.php`
- `wallet/component/admin/src/Event/RegisterSourceAdaptersEvent.php`
- `wallet/component/admin/src/Registry/SourceAdapterRegistry.php`
- `wallet/component/admin/src/Repository/PassRepository.php`
- `wallet/component/admin/src/Service/PassService.php`
- Modify `XdecarowalletComponent.php`
- Tests: `source-registry-runtime.php`, `pass-sync-runtime.php`

**Interfaces:**
- `SourceAdapterInterface::getComponent(): string`
- `SourceAdapterInterface::supports(string $sourceType, string $passType): bool`
- `SourceAdapterInterface::getPassPayload(string $sourceType, string $sourceUuid, string $passType): ?array`
- `SourceAdapterInterface::validate(string $sourceType, string $sourceUuid, string $passType): array`
- `PassService::sync(string $sourceComponent, string $sourceType, string $sourceUuid, string $passType): ?object`
- `PassService::getBySource(string $sourceComponent, string $sourceType, string $sourceUuid, string $passType): ?object`

- [ ] Write tests for plugin registration, duplicate adapter rejection, unsupported source and repeated same-card sync.
- [ ] Implement typed registration event and import only plugin group `xdecarowallet` when adapters are required.
- [ ] Implement `PassRepository` using `DatabaseInterface`, bound values and focused load/upsert/update methods.
- [ ] Implement `PassService::sync()`: resolve adapter -> obtain normalized payload -> canonicalize/hash SHA-256 -> upsert exactly one pass -> no-op on unchanged hash.
- [ ] Missing source follows a controlled invalid/revocation path; never create a duplicate.
- [ ] Expose `PassService` from `XdecarowalletComponent` for conditional Membership calls.
- [ ] Run registry/idempotency/hash tests.
- [ ] Commit `feat: add provider-neutral Wallet pass service`.

---

### Task 5: Implement secure QR lifecycle and authoritative verification

**Files:**
- `wallet/component/admin/src/Service/VerificationTokenService.php`
- `wallet/component/admin/src/Service/VerificationService.php`
- site Verify controller/view/template and routing/service files
- site language files IT/EN/FR
- tests: `token-runtime.php`, `verification-runtime.php`, `verification-output-contract.php`

**Interfaces:**
- `VerificationTokenService::issue(string $passUuid): string`
- `VerificationTokenService::rotate(string $passUuid): string`
- `VerificationTokenService::revoke(string $passUuid): void`
- `VerificationService::verifyToken(string $rawToken): array`

- [ ] Write tests proving raw token is never stored; entropy is 256 bits; hash lookup is SHA-256; rotation invalidates old token; malformed/revoked/unknown tokens fail closed.
- [ ] Generate with `random_bytes(32)` plus URL-safe encoding; persist only hash.
- [ ] Verification flow: token -> Wallet pass -> registered source adapter -> live `validate()` result.
- [ ] If adapter/source is unavailable return `unavailable`, never infer validity from cached Wallet status/payload.
- [ ] Public page exposes only allowed minimal fields, fully escaped; never source/person UUID, token hash, provider IDs or stack traces.
- [ ] Add token-format validation before lookup, indexed lookup, uniform invalid responses and coarse configurable rate limiting.
- [ ] Log only pass UUID, timestamp, result and channel by default; no raw token/IP/user-agent by default.
- [ ] Test valid/expired/suspended/revoked/unknown/rotated/missing-source/unavailable cases.
- [ ] Commit `feat: add secure Wallet QR verification`.

---

### Task 6: Connect successful Membership card saves to Wallet sync

**Files:**
- Modify `membership/component/admin/src/Model/RecordModel.php`
- Modify/test `WalletIntegrationService.php`
- Tests: `membership/tests/wallet-sync-runtime.php`, `wallet/tests/membership-integration-runtime.php`

**Interfaces:**
- Membership calls `WalletIntegrationService::syncCard($cardUuid)` only after its own persistence/business post-save logic succeeds.
- Integration calls `PassService::sync('com_decaromembership', 'card', $uuid, 'membership_card')`.

- [ ] Write test proving Membership save commits even if Wallet sync throws afterward.
- [ ] Add post-save sync for cards after existing DCL/card post-save logic.
- [ ] Safe initial behavior may sync on every successful card save; payload hash makes unchanged saves no-op.
- [ ] Test with Wallet installed, disabled and absent.
- [ ] Commit `feat: sync Membership cards with Wallet`.

---

### Task 7: Add Wallet admin foundation UI

**Files:**
- Admin MVC/templates for Dashboard, Passes, Diagnostics, Information
- Web Asset Manager media files
- Language files IT/EN/FR
- Tests: `admin-ui-contract.php`, `acl-csrf-contract.php`

- [ ] Contract tests require search, source/status filters, pagination, responsive mobile fallback, and ACL+CSRF on resync/rotate/revoke.
- [ ] Dashboard shows pass count, Membership integration health, QR health, recent technical failures; Apple/Google appear as `not configured` placeholders only.
- [ ] Passes view exposes technical/source identifiers without unnecessary personal data.
- [ ] Diagnostics checks schema, registered adapters and source availability; no secrets.
- [ ] Information follows Xdecaro shared structure.
- [ ] Verify desktop/tablet/mobile, light/dark, keyboard focus and no horizontal overflow.
- [ ] Commit `feat: add Wallet administrator foundation`.

---

### Task 8: Phase A build/install/regression gate

**Files:**
- Finalize Wallet `0.1.0` manifest/package/changelog/updater metadata
- Finalize next Membership patch metadata generated from the reconciled baseline

- [ ] Run all Wallet tests plus all affected Membership regression tests; zero failures.
- [ ] Upgrade a database containing pre-UUID cards: row count unchanged, every UUID unique, card numbers/statuses/links unchanged.
- [ ] Clean-install `pkg_xdecarowallet_0.1.0.zip`; verify schema, ACL, languages and admin views.
- [ ] Full QR path: Membership card -> Wallet pass -> QR -> valid -> change Membership to suspended/expired -> same Wallet pass returns new live result.
- [ ] Disable/uninstall Wallet and save/edit Membership cards; no fatal and Membership data untouched.
- [ ] Check PHP error log and browser Console; zero new actionable errors.
- [ ] Inspect ZIP for backups/temp/IDE/log/secrets/nested ZIPs; verify all Wallet version metadata = `0.1.0`.
- [ ] Commit `test: stabilize Wallet 0.1.0 foundation`.

## Phase A Completion Gate

Phase A is complete only when `0.1.0` is installable, Membership remains independent, one Membership card maps idempotently to one Wallet pass, secure QR verification returns live Membership truth, migration preserves every existing card, administrator diagnostics are usable, and uninstall/disable Wallet leaves Membership operational. Apple/Google provider code begins only after this gate passes.
