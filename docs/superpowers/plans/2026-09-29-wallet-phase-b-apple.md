# Xdecaro Wallet Phase B — Apple Wallet Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a production-grade Apple Wallet provider to the Wallet foundation, including signed `.pkpass` generation, Add to Apple Wallet delivery, device registration, pass update web service and safe revocation/update handling.

**Architecture:** Apple-specific logic remains isolated behind a provider service. Wallet keeps the stable pass identity and Membership remains authoritative for business validity. Apple device registrations and update state are Wallet-owned technical data; provider errors never mutate Membership state.

**Tech Stack:** PHP 8.x, Joomla HTTP/routing/services/database APIs, OpenSSL, ZIP generation, Apple Wallet Passes web service over HTTPS, APNs pass-update notifications, Xdecaro Wallet Phase A services.

**Spec:** `docs/superpowers/specs/2026-09-29-wallet-architecture-design.md`

## Global Constraints

- Phase A completion gate must already be green.
- Signing certificate/private key/password must never be committed, logged or rendered.
- `passTypeIdentifier` must match the signing certificate.
- Existing pass updates preserve the same Apple `serialNumber` and `passTypeIdentifier`.
- Apple update web service authenticates registration/unregistration/pass download requests with the pass `authenticationToken`.
- Production update web service uses HTTPS.
- Apple provider failure never rolls back Membership or provider-neutral Wallet state.

## Review Focus

- Wrong/expired certificate or mismatched `passTypeIdentifier`: diagnostics must fail clearly without leaking secrets.
- Repeated pass generation: stable serial number, no duplicate Apple provider item.
- Unauthorized Apple web-service request: return the correct rejection without revealing whether unrelated passes exist.
- Device registration retry: idempotent registration; no duplicate device/pass mapping.
- Pass source changes while APNs fails: provider remains retryable and source state remains correct.

---

### Task 1: Add Apple provider configuration and credential diagnostics

**Files:**
- Create: `wallet/component/admin/src/Provider/Apple/AppleConfig.php`
- Create: `wallet/component/admin/src/Provider/Apple/AppleCredentialService.php`
- Modify: Wallet component configuration XML and Providers/Diagnostics admin view
- Create tests: `wallet/tests/apple-config-contract.php`, `wallet/tests/apple-credential-runtime.php`

**Interfaces:**
- `AppleConfig::fromComponentParams(Registry $params): self`
- `AppleCredentialService::diagnose(): array`
- Credentials are referenced by secure server-side paths/environment-backed configuration, not stored as raw secrets in diagnostic output.

- [ ] Write failing tests for missing, unreadable, expired and mismatched certificate configuration.
- [ ] Implement configuration object with explicit `passTypeIdentifier`, `teamIdentifier`, certificate path, private-key path/reference, optional password reference, web-service base URL and enabled flag.
- [ ] Implement credential diagnostics using OpenSSL metadata; return only status, subject identifier/expiry where safe, and actionable error codes.
- [ ] Add provider admin configuration/diagnostics UI with masked/never-echoed secret fields.
- [ ] Run tests and commit `feat: add Apple Wallet provider configuration`.

---

### Task 2: Implement fixed `membership_card` Apple pass rendering and signing

**Files:**
- Create: `wallet/component/admin/src/Provider/Apple/ApplePassMapper.php`
- Create: `wallet/component/admin/src/Provider/Apple/ApplePassSigner.php`
- Create: `wallet/component/admin/src/Provider/Apple/AppleProvider.php`
- Create fixed template/assets under `wallet/component/media/apple/membership_card/`
- Create tests: `wallet/tests/apple-pass-mapper.php`, `wallet/tests/apple-signing-runtime.php`

**Interfaces:**
- `ApplePassMapper::map(object $pass, array $payload, string $verificationUrl): array`
- `ApplePassSigner::buildPkpass(array $passJson, array $assetFiles): string`
- `AppleProvider::sync(object $walletPass): array`

- [ ] Write mapper tests asserting required Apple fields, stable serial number derived from Wallet pass UUID, barcode pointing to secure verification URL, localized labels and no unapproved personal fields.
- [ ] Implement generic-pass mapping for Membership using the fixed Wallet template; do not build a visual template designer.
- [ ] Implement manifest creation, SHA digest generation, PKCS#7 signing and ZIP packaging with MIME `application/vnd.apple.pkpass`.
- [ ] Ensure temporary build files are created outside public paths and always cleaned up.
- [ ] Add signing tests with non-production fixtures that contain no real secret material.
- [ ] Commit `feat: generate signed Apple Wallet passes`.

---

### Task 3: Persist Apple technical identity and serve Add to Apple Wallet downloads

**Files:**
- Modify: `wallet/component/admin/src/Service/PassService.php`
- Modify/add provider repository methods for `#__xdecarowallet_provider_items`
- Create site controller/action for Apple download
- Modify consumer UI integration to expose Apple button only when configured
- Create tests: `wallet/tests/apple-download-runtime.php`, `wallet/tests/apple-idempotency.php`

**Interfaces:**
- Provider item fields use `provider='apple'`, stable `serial_number=<wallet-pass-uuid>` and `external_id=<passTypeIdentifier>:<serialNumber>`.
- `AppleProvider::download(string $passUuid): response` regenerates current pass from authoritative Wallet snapshot/source validation path.

- [ ] Write tests proving one Apple provider item per Wallet pass and stable serial across resyncs.
- [ ] Implement provider-item upsert and download controller with authorization appropriate to the consumer surface.
- [ ] Return correct MIME/content-disposition and no-cache policy appropriate for generated pass content.
- [ ] Render official Add to Apple Wallet affordance only when Apple diagnostics are healthy enough to succeed.
- [ ] Commit `feat: add Apple Wallet download flow`.

---

### Task 4: Implement Apple device/pass registration schema and web-service endpoints

**Files:**
- Add SQL tables: `#__xdecarowallet_apple_devices`, `#__xdecarowallet_apple_registrations`
- Create: `wallet/component/admin/src/Provider/Apple/AppleRegistrationRepository.php`
- Create site/API controllers for Apple Wallet update endpoints
- Create tests: `wallet/tests/apple-registration-runtime.php`, `wallet/tests/apple-webservice-auth.php`

**Interfaces:**
- Device unique key: `device_library_identifier`.
- Registration unique key: `(device_library_identifier, pass_type_identifier, serial_number)`.
- Endpoints implement Apple registration, unregister, changed-serial lookup and updated-pass retrieval semantics.

- [ ] Write failing endpoint/auth/idempotency tests before schema/code.
- [ ] Add device/registration tables without personal Membership data.
- [ ] Implement registration endpoint: authenticate `ApplePass <authenticationToken>`, upsert device push token, upsert registration.
- [ ] Implement unregister endpoint: authenticate, remove relationship, remove orphan device row.
- [ ] Implement changed-serial endpoint using provider update tag/timestamp.
- [ ] Implement updated-pass endpoint returning regenerated signed `.pkpass` for the same serial/pass type.
- [ ] Verify unauthorized calls return controlled status and no diagnostics leakage.
- [ ] Commit `feat: add Apple Wallet update web service`.

---

### Task 5: Add APNs update notifications and retry-safe synchronization

**Files:**
- Create: `wallet/component/admin/src/Provider/Apple/ApplePushService.php`
- Modify: `AppleProvider`, provider item metadata/update tag handling
- Add manual resync/retry action to admin UI
- Create tests: `wallet/tests/apple-push-runtime.php`, `wallet/tests/apple-sync-failure.php`

**Interfaces:**
- `ApplePushService::notifyPass(string $passUuid): array`
- `AppleProvider::sync()` updates Wallet provider state first, then notifies registered devices; push failure records technical error but does not alter Membership.

- [ ] Write tests for zero registrations, multiple devices, transient APNs failure and invalid push token cleanup.
- [ ] Implement APNs request path using the same signing identity required for pass updates, with empty JSON payload as required by Apple pass update flow.
- [ ] Mark provider item `sync_pending`/`error` on recoverable failure and expose safe retry.
- [ ] Remove device registration when APNs reports an invalid push token according to provider semantics.
- [ ] Commit `feat: push Apple Wallet pass updates`.

---

### Task 6: Apple end-to-end gate

**Files:**
- Update Wallet tests/docs/changelog/provider diagnostics as needed.

- [ ] With non-production/test Apple credentials, generate and open a Membership `.pkpass` and verify signature/visual fields.
- [ ] Install pass on a compatible Apple device when credentials/environment permit.
- [ ] Change Membership card status/expiry/name-visible field and verify same serial updates rather than creating a new pass.
- [ ] Verify registration/unregistration and updated-pass web-service calls.
- [ ] Verify Wallet/Membership remain correct when certificate/APNs/network is unavailable.
- [ ] Run all Phase A + Apple tests, PHP error checks, browser Console checks and responsive/light/dark review of Apple controls.
- [ ] Commit stabilization as `test: stabilize Apple Wallet provider`.

## Phase B Completion Gate

Apple is complete only when Wallet can create a correctly signed pass, serve it with stable identity, register devices, authenticate update requests, return updated passes with the same serial/pass type, notify registered devices, isolate provider failures, and expose no signing secrets.
