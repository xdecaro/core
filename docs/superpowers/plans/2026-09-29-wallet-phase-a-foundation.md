# Xdecaro Wallet Phase A — Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver the first installable Wallet foundation with Membership as the first source, stable source UUIDs, provider-neutral pass storage, secure QR verification, basic administrator UI, and optional/failure-isolated integration.

**Architecture:** Membership remains authoritative for cards and business validity. Wallet stores only digital-pass state, provider-neutral snapshots, opaque verification tokens and technical diagnostics. Membership integrates through an optional `xdecarowallet` provider plugin plus a conditional `WalletIntegrationService`; Wallet never reads Membership tables directly.

**Tech Stack:** PHP 8.x, Joomla MVC/service providers/plugins/events/ACL/CSRF, Joomla Database API, MySQL/MariaDB with `#__` prefixes, Xdecaro Core shared UI primitives where already available, plain JavaScript through Web Asset Manager.

**Spec:** `docs/superpowers/specs/2026-09-29-wallet-architecture-design.md`

## Global Constraints

- Membership cards remain in Membership; Wallet must never become the source of membership validity.
- Wallet is optional: Membership must save and operate normally when Wallet is absent, disabled or misconfigured.
- No Wallet-specific API is added to Core in this phase.
- QR tokens contain no personal data and only their SHA-256 hash is stored.
- Provider failures must never roll back an already successful Membership transaction.
- Use Joomla ACL, CSRF validation, filtered input, escaped output and bound queries for every state-changing/public endpoint.
- Use Semantic Versioning and never publish different code with the same version.
- The current live Membership repository reports `VERSION` 1.9.33; execution must first reconcile that repository with any newer tested/released source before assigning the next patch version, so newer work is never overwritten.
- Joomla 6.x is the primary runtime baseline; avoid unnecessary incompatibilities with Joomla 4/5 where the implementation remains clean.

## Review Focus

- Existing Membership cards without UUID: migration must backfill every row before the unique/not-null constraint is enforced.
- Wallet missing or disabled: card save must still succeed with no fatal class/service lookup.
- Repeated sync of the same card: exactly one Wallet pass and one active QR token relationship, no duplicates.
- Invalid/revoked token or missing Membership source: verification must fail closed and must not leak source/provider diagnostics.
- Membership source temporarily unavailable: verification returns `unavailable`, never `valid` from cached Wallet state.

---

### Task 1: Reconcile the authoritative Membership baseline and add stable card UUIDs

**Files:**
- Inspect/modify: `membership/VERSION`
- Modify: `membership/component/admin/sql/install.mysql.utf8mb4.sql`
- Create: `membership/component/admin/sql/updates/mysql/<next-membership-version>.sql`
- Modify: `membership/component/admin/src/Config/CaseEntities.php`
- Create: `membership/component/admin/src/Service/CardIdentityService.php`
- Modify: `membership/component/admin/src/Model/RecordModel.php`
- Modify as required by existing release process: `membership/component/decaromembership.xml`, package manifest, changelog, updater metadata
- Create test: `membership/tests/card-uuid-contract.php`
- Create test: `membership/tests/card-uuid-runtime.php`

**Interfaces:**
- Produces: `CardIdentityService::generate(): string`
- Produces: immutable `#__decaromembership_cards.uuid CHAR(36)` with unique index `uq_card_uuid`
- Later tasks consume Membership card UUID as `source_uuid`.

- [ ] **Step 1: Reconcile the source baseline before editing**

Compare `membership/main`, current release metadata and the latest tested installable Membership source. If a newer tested version exists outside `main`, first bring `main` to that exact code/version without changing behavior. Verify `VERSION`, manifest/package versions, changelog and updater metadata agree before continuing.

- [ ] **Step 2: Write the UUID contract test**

`tests/card-uuid-contract.php` must assert that the fresh-install schema contains `uuid CHAR(36)`, `uq_card_uuid`, and that the update migration performs add -> backfill -> not-null/unique in that order. It must also assert `CaseEntities` does not expose UUID as an editable administrator field.

- [ ] **Step 3: Run the contract test and verify it fails before implementation**

Run the repository's existing PHP test convention for `tests/card-uuid-contract.php`.
Expected: FAIL because the UUID column/service/migration do not yet exist.

- [ ] **Step 4: Add the schema migration and fresh-install schema**

Fresh install: add `uuid CHAR(36) NOT NULL` and unique key `uq_card_uuid` to `#__decaromembership_cards`.
Update migration: add the column nullable, backfill every existing missing value with a unique UUID, verify no null/empty/duplicate values remain, then enforce `NOT NULL` and the unique key. Use `#__` table naming.

- [ ] **Step 5: Implement `CardIdentityService::generate(): string`**

Generate RFC 4122-style lowercase UUID v4 using `random_bytes(16)` and correct version/variant bits; do not add a third-party dependency.

- [ ] **Step 6: Preserve UUID on edits and generate it only for new cards**

In `RecordModel::saveEntity()`, for entity `cards`, preserve the stored UUID on edits and assign `CardIdentityService::generate()` before persistence when a new card has no UUID. UUID must never be user-editable or regenerated during ordinary edits.

- [ ] **Step 7: Write and run the runtime UUID test**

`tests/card-uuid-runtime.php` must prove: two new cards get different valid UUIDs; editing a card preserves its UUID; an existing migrated card keeps the same UUID across subsequent saves.

- [ ] **Step 8: Run Membership regression tests**

Run the existing Membership card, DCL, lifecycle, install/update and package tests. Expected: all previously passing tests stay green.

- [ ] **Step 9: Commit**

Commit only the reconciled baseline/UUID work with a message equivalent to `feat: add stable membership card UUIDs`.

---

### Task 2: Add the Membership Wallet source service and optional Wallet provider plugin

**Files:**
- Create: `membership/component/admin/src/Service/WalletSourceService.php`
- Create: `membership/component/admin/src/Service/WalletIntegrationService.php`
- Modify: `membership/component/admin/src/Extension/MembershipComponent.php`
- Create: `membership/plugins/xdecarowallet/decaromembership/decaromembership.xml`
- Create: `membership/plugins/xdecarowallet/decaromembership/services/provider.php`
- Create: `membership/plugins/xdecarowallet/decaromembership/src/Extension/Decaromembership.php`
- Create: `membership/plugins/xdecarowallet/decaromembership/src/Provider/MembershipProvider.php`
- Create language files under `membership/plugins/xdecarowallet/decaromembership/language/{it-IT,en-GB,fr-FR}/`
- Modify Membership package manifest/build so the plugin ships with Membership
- Create test: `membership/tests/wallet-source-contract.php`
- Create test: `membership/tests/wallet-optional-runtime.php`

**Interfaces:**
- Produces: `WalletSourceService::getPassPayload(string $sourceUuid): ?array`
- Produces: `WalletSourceService::validateCard(string $sourceUuid): array`
- Produces: `WalletIntegrationService::isAvailable(): bool`
- Produces: `WalletIntegrationService::syncCard(string $sourceUuid): void`
- Provider implements Wallet's `SourceAdapterInterface` only when Wallet imports plugin group `xdecarowallet`.

- [ ] **Step 1: Write source-contract tests first**

Assert that a Membership payload contains only: `pass_type`, `source_uuid`, `person_uuid`, `issuer_organization_uuid`, `display_name`, `card_number`, `category_label`, `valid_from`, `expires_at`, `display_status`, optional `photo_reference`, `issuer_name`, `issuer_logo_reference`, `locale`. Assert no tax code, payment, notes or document data can appear.

- [ ] **Step 2: Implement `WalletSourceService`**

Load the card by UUID through Membership's own repository/database layer; resolve only the minimum People/Organizations/category display data needed; return `null` for a missing/unpublished source. `validateCard()` must derive current truth from Membership status/dates/source existence and return one of `valid`, `invalid`, `expired`, `suspended`, `revoked`, `unavailable` with the minimum public display fields.

- [ ] **Step 3: Expose `WalletSourceService` from `MembershipComponent`**

Follow the existing component service/getter pattern used for cross-product integrations. Do not expose raw repositories or tables.

- [ ] **Step 4: Implement `WalletIntegrationService` as a soft dependency**

`isAvailable()` checks `com_xdecarowallet` installation/enabled state and bootability. `syncCard()` boots Wallet only when available and calls its public pass service without importing/type-hinting Wallet classes at Membership file-load time. Catch provider/Wallet failures, log sanitized warnings and return without breaking Membership.

- [ ] **Step 5: Add `plg_xdecarowallet_decaromembership`**

Mirror the existing `plugins/xdecaroanalytics/decaromembership` registration pattern: subscribe to Wallet's typed `RegisterSourceAdaptersEvent`, boot `com_decaromembership`, obtain `WalletSourceService`, and register `MembershipProvider`.

- [ ] **Step 6: Test Wallet absence**

`tests/wallet-optional-runtime.php` must run Membership card save/source operations with Wallet unavailable and assert no fatal/error is produced and the Membership card persists normally.

- [ ] **Step 7: Commit**

Commit with a message equivalent to `feat: expose membership cards to Wallet`.

---

### Task 3: Create the Wallet repository/package/component skeleton and database

**Files in new repository `xdecaro/wallet`:**
- Create: `AGENTS.md`, `README.md`, `CHANGELOG.md`, `VERSION`, `.gitignore`
- Create: `component/xdecarowallet.xml`
- Create: `component/admin/access.xml`
- Create: `component/admin/config.xml`
- Create: `component/admin/services/provider.php`
- Create: `component/admin/src/Extension/XdecarowalletComponent.php`
- Create: `component/admin/sql/install.mysql.utf8mb4.sql`
- Create: `component/admin/sql/uninstall.mysql.utf8mb4.sql`
- Create: `component/admin/sql/updates/mysql/1.0.0.sql`
- Create: `component/admin/language/{it-IT,en-GB,fr-FR}/com_xdecarowallet.ini`
- Create: `component/admin/language/{it-IT,en-GB,fr-FR}/com_xdecarowallet.sys.ini`
- Create: `package/pkg_xdecarowallet/pkg_xdecarowallet.xml`
- Create: `package/pkg_xdecarowallet/script.php`
- Create build/update-server files following the current Xdecaro package convention
- Create tests: `tests/schema-contract.php`, `tests/package-contract.php`, `tests/language-contract.php`

**Interfaces:**
- Produces Joomla component `com_xdecarowallet` and package `pkg_xdecarowallet` version `1.0.0` during development; do not publish release until Phase D acceptance passes.
- Produces tables `#__xdecarowallet_passes`, `#__xdecarowallet_provider_items`, `#__xdecarowallet_verification_tokens`, `#__xdecarowallet_verification_events`.

- [ ] **Step 1: Create the repository/worktree or local isolated source tree**

Use repository `xdecaro/wallet`. If repository creation is not available through the active GitHub connector, build in an isolated local `wallet` source tree and defer only the repository publication step; do not place Wallet product code inside Core or Membership.

- [ ] **Step 2: Write schema/package/language tests before implementation**

Tests must assert package/component identity, namespace, required ACL actions, all four tables and their critical unique/index constraints, `#__` usage, and complete base language keys in IT/EN/FR.

- [ ] **Step 3: Implement the four tables exactly from the approved design**

Passes unique key: `(source_component, source_type, source_uuid, pass_type)`.
Provider items unique key: `(pass_uuid, provider)`.
Verification token stores `token_hash CHAR(64)` unique, never raw token.
Verification events store no required personal-data columns.

- [ ] **Step 4: Add ACL and service-provider skeleton**

At minimum define view/manage, pass resync/manage, token rotate/revoke, diagnostics and configuration actions. Register the component and future service objects through Joomla DI/service provider conventions.

- [ ] **Step 5: Build the component/package and run clean-install static checks**

Expected: the generated package has no extra parent directory, no source-only/temp files, and all manifests report the same version.

- [ ] **Step 6: Commit**

Commit with a message equivalent to `feat: bootstrap Xdecaro Wallet component`.

---

### Task 4: Implement Wallet source registration, persistence and idempotent pass synchronization

**Files:**
- Create: `wallet/component/admin/src/Contract/SourceAdapterInterface.php`
- Create: `wallet/component/admin/src/Event/RegisterSourceAdaptersEvent.php`
- Create: `wallet/component/admin/src/Registry/SourceAdapterRegistry.php`
- Create: `wallet/component/admin/src/Repository/PassRepository.php`
- Create: `wallet/component/admin/src/Service/PassService.php`
- Modify: `wallet/component/admin/src/Extension/XdecarowalletComponent.php`
- Create tests: `wallet/tests/source-registry-runtime.php`, `wallet/tests/pass-sync-runtime.php`

**Interfaces:**
- `SourceAdapterInterface::getComponent(): string`
- `SourceAdapterInterface::supports(string $sourceType, string $passType): bool`
- `SourceAdapterInterface::getPassPayload(string $sourceType, string $sourceUuid, string $passType): ?array`
- `SourceAdapterInterface::validate(string $sourceType, string $sourceUuid, string $passType): array`
- `PassService::sync(string $sourceComponent, string $sourceType, string $sourceUuid, string $passType): ?object`
- `PassService::getBySource(string $sourceComponent, string $sourceType, string $sourceUuid, string $passType): ?object`

- [ ] **Step 1: Write registry and duplicate-sync tests**

Assert that `xdecarowallet` plugins can register adapters; duplicate adapter keys are rejected deterministically; unsupported sources fail cleanly. Syncing the same Membership card twice must return the same Wallet pass UUID and not insert a second row.

- [ ] **Step 2: Implement typed registration event and registry**

Import/dispatch only the `xdecarowallet` plugin group when adapters are needed. Registry key is source component plus supported source/pass pair. Do not hardcode Membership table access into Wallet.

- [ ] **Step 3: Implement `PassRepository`**

Use Joomla `DatabaseInterface`, bound parameters and the unique source tuple. Provide focused load/upsert/update methods; no business validation belongs here.

- [ ] **Step 4: Implement `PassService::sync()`**

Resolve the adapter, request the provider-neutral payload, canonicalize/hash it with SHA-256, create or update exactly one Wallet pass, and avoid writes/external work when the payload hash is unchanged. Source absence results in a controlled null/revocation path, never a duplicate.

- [ ] **Step 5: Expose `PassService` from `XdecarowalletComponent`**

This is the public entry point used conditionally by Membership's `WalletIntegrationService`.

- [ ] **Step 6: Run source registry/pass synchronization tests**

Expected: adapter registration, missing-source handling, idempotent sync and payload-hash no-op tests all pass.

- [ ] **Step 7: Commit**

Commit with a message equivalent to `feat: add provider-neutral Wallet pass service`.

---

### Task 5: Implement secure QR token lifecycle and authoritative public verification

**Files:**
- Create: `wallet/component/admin/src/Service/VerificationTokenService.php`
- Create: `wallet/component/admin/src/Service/VerificationService.php`
- Create: `wallet/component/site/src/Controller/VerifyController.php`
- Create: `wallet/component/site/src/View/Verify/HtmlView.php`
- Create: `wallet/component/site/tmpl/verify/default.php`
- Add site language files IT/EN/FR
- Add routing/service files required by current Joomla component pattern
- Create tests: `wallet/tests/token-runtime.php`, `wallet/tests/verification-runtime.php`, `wallet/tests/verification-output-contract.php`

**Interfaces:**
- `VerificationTokenService::issue(string $passUuid): string`
- `VerificationTokenService::rotate(string $passUuid): string`
- `VerificationTokenService::revoke(string $passUuid): void`
- `VerificationService::verifyToken(string $rawToken): array`

- [ ] **Step 1: Write security tests first**

Assert raw token is never persisted; token entropy is at least 256 bits before encoding; lookup uses SHA-256 hash; rotation invalidates the previous token; revoked/unknown/malformed tokens fail closed.

- [ ] **Step 2: Implement token service**

Generate tokens with `random_bytes(32)` and URL-safe encoding. Persist only `hash('sha256', $rawToken)`. Keep issuance/rotation/revocation independent from source UUID/card number.

- [ ] **Step 3: Implement authoritative verification**

Resolve token -> Wallet pass -> registered source adapter -> `validate()`. If adapter/source is unavailable, return `unavailable`; never infer valid from Wallet `technical_status` or cached payload.

- [ ] **Step 4: Implement minimal public result page**

Show only verification state and approved minimal fields. Escape all values. Never expose source UUID, token hash, person UUID, provider IDs, stack traces or administrator diagnostics.

- [ ] **Step 5: Add abuse controls**

Validate token format before database lookup, use indexed lookup, return uniform invalid responses, and add a configurable coarse rate-limit mechanism suitable for Joomla without storing unnecessary personal data.

- [ ] **Step 6: Implement verification-event logging with minimization**

Persist pass UUID, timestamp, result and channel only by default. No raw token, IP or user-agent retention by default.

- [ ] **Step 7: Run QR verification tests**

Cover valid, expired, suspended, revoked, unknown token, rotated token, missing source and source unavailable.

- [ ] **Step 8: Commit**

Commit with a message equivalent to `feat: add secure Wallet QR verification`.

---

### Task 6: Connect Membership card saves to Wallet synchronization without transaction coupling

**Files:**
- Modify: `membership/component/admin/src/Model/RecordModel.php`
- Modify/test: `membership/component/admin/src/Service/WalletIntegrationService.php`
- Create: `membership/tests/wallet-sync-runtime.php`
- Create: `wallet/tests/membership-integration-runtime.php`

**Interfaces:**
- Consumes: `WalletIntegrationService::syncCard(string $sourceUuid): void`
- Consumes: `PassService::sync('com_decaromembership', 'card', $uuid, 'membership_card')`

- [ ] **Step 1: Write the transaction-isolation tests**

Assert that the Membership repository save succeeds first; a simulated Wallet exception afterward does not undo the card save. Assert unchanged card payload does not create a second pass.

- [ ] **Step 2: Add post-save synchronization hook for `cards`**

After Membership persistence and existing DCL/card-specific post-save logic have successfully completed, call `WalletIntegrationService::syncCard($cardUuid)`. Do not move Wallet work inside Membership database transaction semantics.

- [ ] **Step 3: Trigger synchronization only for relevant card changes**

At minimum synchronize on create and when holder/display identity, issuer, card number, status, validity, category/photo reference or published state can change the pass/validation result. A safe initial implementation may call idempotent sync on every successful card save; payload hash prevents unnecessary Wallet updates.

- [ ] **Step 4: Run Membership-without-Wallet and Membership-with-Wallet tests**

Expected: both modes pass; Wallet error logs are sanitized and Membership card state remains correct.

- [ ] **Step 5: Commit**

Commit with a message equivalent to `feat: sync Membership cards with Wallet`.

---

### Task 7: Add the Wallet administrator foundation UI and diagnostics

**Files:**
- Create Wallet admin MVC for: Dashboard, Passes, Diagnostics, Information
- Create `wallet/component/admin/tmpl/dashboard/default.php`
- Create `wallet/component/admin/tmpl/passes/default.php`
- Create `wallet/component/admin/tmpl/diagnostics/default.php`
- Create `wallet/component/admin/tmpl/information/default.php`
- Create/modify Web Asset Manager files under `wallet/component/media/`
- Create language keys IT/EN/FR
- Create tests: `wallet/tests/admin-ui-contract.php`, `wallet/tests/acl-csrf-contract.php`

**Interfaces:**
- Pass list consumes `PassRepository` read methods only.
- State-changing actions consume `PassService`/`VerificationTokenService` and require ACL + CSRF.

- [ ] **Step 1: Write UI/ACL contract tests**

Require search, source/pass-status filters, pagination, visible technical status, source identity without personal-data overexposure, responsive card fallback, and ACL+CSRF on resync/rotate/revoke actions.

- [ ] **Step 2: Implement Dashboard and Passes views**

Use Xdecaro Core shared admin primitives where available; no hardcoded light-only colors. Keep toolbar/action patterns consistent with current Xdecaro products.

- [ ] **Step 3: Implement Diagnostics**

Show component/schema health, registered source adapters, Membership detected/not detected, token subsystem health and provider placeholders `Apple: not configured` / `Google: not configured`. Never display secrets.

- [ ] **Step 4: Implement Information page**

Follow the common Xdecaro structure: Product + Environment; included extensions + updates; linked components full width; diagnostics full width.

- [ ] **Step 5: Verify responsive/light/dark/accessibility**

Manual validation on desktop, tablet and smartphone; keyboard focus; no horizontal overflow; meaningful labels; state not communicated only by color.

- [ ] **Step 6: Commit**

Commit with a message equivalent to `feat: add Wallet administrator foundation`.

---

### Task 8: Build, install and regression-gate Phase A

**Files:**
- Modify final phase-A version/changelog/update metadata in Wallet and Membership as required
- Build outputs only under the repositories' existing build/release artifact locations
- Add/update CI workflows if the repository convention requires them

**Interfaces:**
- Produces installable `pkg_xdecarowallet_1.0.0` development candidate and a compatible next-patch Membership package; neither is a final Wallet 1.0 release until Apple/Google and Phase D pass.

- [ ] **Step 1: Run all static/unit/runtime tests in both repositories**

Expected: zero failures in new tests and no regression in existing Membership tests.

- [ ] **Step 2: Test Membership upgrade migration on a database containing pre-UUID cards**

Verify row count unchanged, every card has one unique UUID, card numbers/statuses/links unchanged, and repeated update does not regenerate UUIDs.

- [ ] **Step 3: Clean-install Wallet package on Joomla test runtime**

Verify schema, ACL, menu, languages, admin pages and uninstall behavior. Uninstall Wallet and prove Membership data/cards remain untouched.

- [ ] **Step 4: Test the complete QR path**

Create/save Membership card -> automatic Wallet pass -> issue/show QR -> verify valid -> change Membership to suspended/expired -> same Wallet pass verifies new authoritative result without duplicate pass creation.

- [ ] **Step 5: Test Wallet failure isolation**

Disable/uninstall Wallet and save/edit Membership cards. Expected: Membership remains fully functional and no opaque fatal errors occur.

- [ ] **Step 6: Inspect PHP errors and browser Console**

Expected: no Wallet/Membership warnings, notices, deprecations introduced by this work, and no JavaScript errors on changed pages.

- [ ] **Step 7: Verify ZIP contents and version coherence**

No backups, temp files, IDE files, `.DS_Store`, logs, secrets or nested extra package folder. Manifest/package/updater/changelog versions must agree.

- [ ] **Step 8: Commit the Phase A stabilization changes**

Commit with a message equivalent to `test: stabilize Wallet foundation`.

## Phase A Completion Gate

Phase A is complete only when Wallet is installable, Membership remains independent, one Membership card maps idempotently to one Wallet pass, secure QR verification returns live Membership truth, admin diagnostics are usable, migration preserves all existing cards, and uninstalling/disable Wallet leaves Membership operational. Apple Wallet and Google Wallet provider code are explicitly outside this plan and begin only after this gate passes.
