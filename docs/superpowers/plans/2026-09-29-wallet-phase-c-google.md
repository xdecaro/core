# Xdecaro Wallet Phase C — Google Wallet Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a production-grade Google Wallet provider using GenericClass/GenericObject, signed Add to Google Wallet JWTs, idempotent object lifecycle and safe PATCH-based updates.

**Architecture:** Google-specific API and JWT logic is isolated behind a provider service. Wallet owns provider IDs and technical state; Membership remains authoritative for source validity. One Wallet pass maps to one stable Google GenericObject and a controlled shared GenericClass for the `membership_card` template.

**Tech Stack:** PHP 8.x, Joomla HTTP/services/database APIs, Google Wallet REST API, RS256 JWT signing with service-account credentials, Xdecaro Wallet Phase A services.

**Spec:** `docs/superpowers/specs/2026-09-29-wallet-architecture-design.md`

## Global Constraints

- Phase A completion gate must already be green.
- Google service-account private key/credentials must never be committed, logged or rendered.
- Use `GenericClass` + `GenericObject` for `membership_card`.
- Object IDs and class IDs are stable and derived from configured issuer plus deterministic suffixes; resync must not create duplicates.
- Web Add to Google Wallet flow uses a signed JWT with `aud=google`, `typ=savetowallet` and configured allowed origins.
- Prefer PATCH for partial object updates so omitted fields are not accidentally cleared.
- Provider failure never rolls back Membership or provider-neutral Wallet state.

## Review Focus

- Invalid issuer/service-account configuration: fail with actionable diagnostics, no secret output.
- Repeated add/sync: same GenericObject ID, no duplicate pass object.
- PATCH of arrays: send complete intended array value because Google replaces the array supplied by PATCH.
- Google object removed by end user: object remains reusable/re-addable without creating a new object ID.
- Publishing access not granted/test-only account: diagnostics must distinguish configuration/API state from source validity.

---

### Task 1: Add Google provider configuration and secure credential handling

**Files:**
- Create: `wallet/component/admin/src/Provider/Google/GoogleConfig.php`
- Create: `wallet/component/admin/src/Provider/Google/GoogleCredentialService.php`
- Modify Wallet Providers/Diagnostics/configuration views
- Create tests: `wallet/tests/google-config-contract.php`, `wallet/tests/google-credential-runtime.php`

**Interfaces:**
- `GoogleConfig::fromComponentParams(Registry $params): self`
- `GoogleCredentialService::diagnose(): array`
- Configuration contains issuer ID, service-account email, secure credential reference/path, allowed origin(s), enabled flag, class suffix policy.

- [ ] Write failing tests for missing issuer ID, malformed credential JSON/reference, missing private key/email and unusable configuration.
- [ ] Implement config object without persisting secret JSON into diagnostics/logs.
- [ ] Implement credential loader that exposes only required in-memory signing/auth fields and sanitized diagnostic status.
- [ ] Add admin configuration UI that never echoes private key after save.
- [ ] Commit `feat: add Google Wallet provider configuration`.

---

### Task 2: Implement GenericClass/GenericObject mapping for `membership_card`

**Files:**
- Create: `wallet/component/admin/src/Provider/Google/GoogleClassMapper.php`
- Create: `wallet/component/admin/src/Provider/Google/GoogleObjectMapper.php`
- Create tests: `wallet/tests/google-class-mapper.php`, `wallet/tests/google-object-mapper.php`

**Interfaces:**
- `GoogleClassMapper::map(string $classId, array $templateContext): array`
- `GoogleObjectMapper::map(string $objectId, string $classId, object $walletPass, array $payload, string $verificationUrl): array`

- [ ] Write mapper tests asserting deterministic class/object IDs, localized title/header/issuer fields, active/inactive provider state mapping and barcode value pointing to secure Wallet verification URL.
- [ ] Assert mapper output contains no tax code, internal notes, payments, provider diagnostics or unrelated People fields.
- [ ] Implement one controlled GenericClass for the Membership template and one GenericObject per Wallet pass.
- [ ] Keep class-wide display data separate from person/pass-specific object data.
- [ ] Commit `feat: map Wallet passes to Google Generic passes`.

---

### Task 3: Implement Google Wallet API client and idempotent class/object upsert

**Files:**
- Create: `wallet/component/admin/src/Provider/Google/GoogleApiClient.php`
- Create: `wallet/component/admin/src/Provider/Google/GoogleProvider.php`
- Modify provider-item persistence for `provider='google'`
- Create tests: `wallet/tests/google-api-runtime.php`, `wallet/tests/google-idempotency.php`

**Interfaces:**
- `GoogleApiClient::getClass(string $resourceId): ?array`
- `GoogleApiClient::insertClass(array $body): array`
- `GoogleApiClient::patchClass(string $resourceId, array $body): array`
- `GoogleApiClient::getObject(string $resourceId): ?array`
- `GoogleApiClient::insertObject(array $body): array`
- `GoogleApiClient::patchObject(string $resourceId, array $body): array`
- `GoogleProvider::sync(object $walletPass): array`

- [ ] Write tests with an HTTP fake for 200/404/401/403/429/5xx responses.
- [ ] Implement authenticated REST requests with short bounded timeouts, sanitized errors and no credential material in logs.
- [ ] Implement class create-if-missing and PATCH for changed shared template fields.
- [ ] Implement object create-if-missing and PATCH only for changed fields; if arrays change, send the complete intended replacement arrays.
- [ ] Persist stable `external_id=<issuer>.<objectSuffix>` and technical status in provider items.
- [ ] Prove repeated unchanged sync produces no duplicate insert and no unnecessary patch.
- [ ] Commit `feat: synchronize Google Wallet objects`.

---

### Task 4: Implement signed Add to Google Wallet JWT/link flow

**Files:**
- Create: `wallet/component/admin/src/Provider/Google/GoogleJwtService.php`
- Create site controller/action that returns/redirects to the add flow
- Modify consumer UI to show Google button only when provider is usable
- Create tests: `wallet/tests/google-jwt-runtime.php`, `wallet/tests/google-add-flow.php`

**Interfaces:**
- `GoogleJwtService::createSaveJwt(string $objectId): string`
- `GoogleJwtService::createSaveUrl(string $objectId): string`

- [ ] Write JWT tests asserting `iss=<service-account-email>`, `aud=google`, `typ=savetowallet`, current `iat`, configured `origins`, and `payload.genericObjects[0].id=<stable-object-id>`.
- [ ] Implement RS256 signing with configured service-account private key in memory only.
- [ ] Generate the standard `https://pay.google.com/gp/v/save/<signed_jwt>` URL; keep JWT compact and reference existing object rather than embedding a large full object after API creation.
- [ ] Expose Add to Google Wallet control only when source pass exists and provider diagnostics are healthy enough to succeed.
- [ ] Commit `feat: add Google Wallet save flow`.

---

### Task 5: Map Wallet/source status changes to safe Google object updates

**Files:**
- Modify: `GoogleProvider.php`, `GoogleObjectMapper.php`
- Modify PassService/provider synchronization hook as required
- Create tests: `wallet/tests/google-status-sync.php`, `wallet/tests/google-sync-failure.php`

**Interfaces:**
- Consumes authoritative provider-neutral payload generated from Membership source.
- Produces safe Google object state/visible status update without changing object ID.

- [ ] Write tests for Membership active -> suspended -> expired -> revoked transitions using the same Google object ID.
- [ ] Define exact mapping from Wallet/source state to Google `state` and visible status fields; do not delete the object merely because the Membership card becomes invalid.
- [ ] Use PATCH for state/display changes and record `last_sync_at`, `last_success_at`, sanitized error code/message.
- [ ] Treat transient network/429/5xx failures as retryable technical failures; do not alter source truth.
- [ ] Add safe manual resync action through existing Wallet admin controls.
- [ ] Commit `feat: update Google Wallet pass state`.

---

### Task 6: Google end-to-end gate

**Files:**
- Update tests/docs/changelog/provider diagnostics as needed.

- [ ] With a Google Wallet test issuer/service account, create or ensure the Membership GenericClass and a GenericObject.
- [ ] Generate the signed save URL and save/re-open the pass where test publishing permissions allow.
- [ ] Verify repeated Add action/re-add after user removal reuses the same object rather than creating duplicates.
- [ ] Change source-visible fields/status and confirm the existing object updates through PATCH.
- [ ] Verify provider/API failure does not corrupt Wallet pass or Membership card state.
- [ ] Run all Phase A + Google tests, PHP error checks, browser Console checks and responsive/light/dark review of Google controls.
- [ ] Commit stabilization as `test: stabilize Google Wallet provider`.

## Phase C Completion Gate

Google is complete only when Wallet can create/reuse the GenericClass, create one stable GenericObject per Wallet pass, generate a correctly signed Add to Google Wallet link, update existing objects safely with PATCH, isolate API failures and expose no service-account secrets.
