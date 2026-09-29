# Xdecaro Wallet — Architecture Design

Date: 2026-09-29
Status: Design approved in chat; written specification pending final user review
Scope: ecosystem architecture and first implementation target

## 1. Purpose

Xdecaro Wallet is a shared Joomla product that converts records owned by Xdecaro domain components into digital passes usable through Apple Wallet, Google Wallet and a secure QR verification flow.

Wallet does not replace the source domain. Membership remains the owner of membership cards; Competitions remains the owner of accreditations; Events remains the owner of tickets or event credentials; Courses remains the owner of course badges; Bookings remains the owner of booking credentials.

Wallet owns only the digital representation, provider synchronization, verification transport and technical lifecycle of those representations.

The first real consumer is Membership.

## 2. Goals

Wallet 1.0 must provide:

- a reusable digital-pass engine independent from Membership business rules;
- optional integration with Membership without making Membership depend on Wallet for normal operation;
- one digital pass mapped to one stable source record;
- secure QR verification without exposing personal data in the QR payload;
- Apple Wallet pass generation and lifecycle support;
- Google Wallet pass generation and lifecycle support;
- provider diagnostics and controlled failure handling;
- administrator views consistent with the Xdecaro ecosystem;
- responsive, accessible, light/dark compatible UI;
- a stable path for later integration with Competitions, Events, Courses and Bookings;
- an architecture that can add NFC/contactless support later without redesigning the source-domain model.

## 3. Non-goals for 1.0

Wallet 1.0 will not:

- move Membership cards out of Membership;
- own membership eligibility, renewals, dues, transfers or card numbering rules;
- duplicate People, Membership or Organizations master data;
- introduce a generic visual pass designer;
- implement physical access-control hardware;
- require NFC for normal use;
- place Apple- or Google-specific provider code in Xdecaro Core;
- require Wallet to be installed for Membership, Competitions or other components to function.

## 4. Product boundary

### 4.1 Membership owns the card

Membership remains authoritative for:

- member identity relation;
- membership card number;
- issuer organization;
- card scope/program/season;
- card status;
- issue, activation and validity dates;
- renewals and expiry;
- membership eligibility and business rules;
- card numbering and sequences;
- any ENS- or association-specific semantics.

The existing `#__decaromembership_cards` table already contains the core Membership card data and remains the source of truth.

### 4.2 Wallet owns the digital pass

Wallet owns:

- pass UUID;
- link to the source component and source record;
- pass template key;
- provider-neutral rendering snapshot;
- Apple Wallet technical identity and synchronization state;
- Google Wallet technical identity and synchronization state;
- secure public verification token lifecycle;
- technical pass status;
- provider errors and synchronization diagnostics;
- verification audit events with data minimization.

Wallet must never decide that a Membership card is valid merely because a cached Wallet record says `active`. Source-domain validation is authoritative.

## 5. Core boundary

No Wallet-specific API is added to Xdecaro Core for the first implementation.

Rationale:

- the first real consumer is Membership;
- extracting a generic contract before multiple real consumers exist would be speculative;
- Core must remain domain-neutral and small;
- Apple/Google/QR provider logic clearly belongs outside Core.

Wallet may consume existing Core UI primitives, diagnostics conventions and shared utilities.

After at least two or three real products integrate with Wallet, repeated integration code may justify extracting only a small generic contract or event definition into Core. Any such extraction is a separate future design decision.

## 6. Proposed extension/package identity

Future repository:

- `xdecaro/wallet`

Visible product name:

- `Wallet`

Primary Joomla component:

- `com_xdecarowallet`

Distribution package:

- `pkg_xdecarowallet`

The initial package may include a small system plugin only if runtime registration/listening is demonstrably required by Joomla lifecycle needs. The component must remain the owner of Wallet data and provider logic. Do not create extra extensions without a concrete responsibility.

## 7. Source identity and stable linking

Wallet must link to stable source identities, not mutable display values such as card number.

For Membership, add a stable UUID to `#__decaromembership_cards` before Wallet integration if the table does not already contain one at implementation time:

```sql
uuid CHAR(36) NOT NULL
```

with a unique index.

The UUID belongs to the Membership card record and is not a global person UUID or a Core-owned identifier.

Wallet uses a generic source tuple:

```text
source_component = com_decaromembership
source_type      = card
source_uuid      = <membership-card-uuid>
```

A unique constraint must prevent duplicate Wallet passes for the same source tuple and pass purpose unless the product explicitly supports multiple independent pass variants later.

## 8. Wallet data model

### 8.1 Passes

Suggested table:

`#__xdecarowallet_passes`

Minimum fields:

- `id` BIGINT UNSIGNED primary key;
- `uuid` CHAR(36) unique and immutable;
- `source_component` VARCHAR(100);
- `source_type` VARCHAR(100);
- `source_uuid` CHAR(36);
- `pass_type` VARCHAR(100), e.g. `membership_card`;
- `template_key` VARCHAR(100);
- `technical_status` VARCHAR(50);
- `payload_json` LONGTEXT containing only the provider-neutral display snapshot required to render the pass;
- `payload_hash` CHAR(64) for change detection;
- `published` TINYINT;
- standard Joomla created/modified metadata.

`payload_json` is a rendering snapshot, not a second source of business truth. Source status and eligibility must be revalidated when correctness matters.

Recommended unique key:

```text
(source_component, source_type, source_uuid, pass_type)
```

### 8.2 Provider items

Suggested table:

`#__xdecarowallet_provider_items`

Minimum fields:

- `id` BIGINT UNSIGNED;
- `pass_uuid` CHAR(36);
- `provider` VARCHAR(30): `apple` or `google`;
- `external_id` VARCHAR(255) nullable;
- `serial_number` VARCHAR(255) nullable;
- `provider_status` VARCHAR(50);
- `last_sync_at` DATETIME nullable;
- `last_success_at` DATETIME nullable;
- `last_error_code` VARCHAR(100) nullable;
- `last_error_message` TEXT nullable, sanitized and secret-free;
- `metadata_json` LONGTEXT nullable for non-sensitive provider metadata;
- created/modified metadata.

A unique key on `(pass_uuid, provider)` prevents duplicate provider objects.

### 8.3 Verification tokens

Suggested table:

`#__xdecarowallet_verification_tokens`

Minimum fields:

- `id` BIGINT UNSIGNED;
- `pass_uuid` CHAR(36);
- `token_hash` CHAR(64) unique;
- `status` VARCHAR(30);
- `created` DATETIME;
- `expires_at` DATETIME nullable;
- `rotated_at` DATETIME nullable;
- `revoked_at` DATETIME nullable.

Only a cryptographic hash of the token is stored. The raw token is shown only in the verification URL/QR and must be generated with a cryptographically secure random source.

Token rotation and revocation must not change the Membership card identity.

### 8.4 Verification events

Suggested table:

`#__xdecarowallet_verification_events`

Store only what is required for security, diagnostics and audit:

- pass UUID;
- timestamp;
- result (`valid`, `invalid`, `expired`, `suspended`, `revoked`, `unavailable`);
- verification channel (`qr`, later `nfc`, admin/test);
- optional minimal request metadata after privacy review.

Do not store unnecessary personal data in verification logs. Avoid indefinite retention of raw IP addresses or user-agent data unless a documented operational requirement exists.

## 9. Provider-neutral pass payload

Source components provide only data required for rendering and validation. A Membership pass payload may contain:

```text
pass_type
source_uuid
person_uuid
issuer_organization_uuid
display_name
card_number
category_label
valid_from
expires_at
display_status
photo_reference (optional)
issuer_name
issuer_logo_reference
locale
```

The payload must not contain private documents, fiscal data, payment history, internal notes or unrelated People fields.

Every field shown publicly or sent to Apple/Google must have a documented purpose.

## 10. Integration contract — Membership first

Membership remains fully functional when Wallet is absent, disabled or misconfigured.

### 10.1 Availability detection

Membership must check Wallet availability through supported Joomla extension/service discovery. Missing Wallet must result only in hidden/disabled Wallet actions, never a fatal error.

### 10.2 Create/update flow

When an eligible Membership card is created or changed:

1. Membership commits its own transaction first.
2. Membership determines whether the change affects the digital pass.
3. If Wallet is installed and enabled, Membership requests synchronization using the stable card UUID.
4. Wallet loads or receives the minimum source data needed to build the provider-neutral payload.
5. Wallet upserts its pass record.
6. Wallet calculates `payload_hash`.
7. Only changed provider payloads are synchronized.
8. Provider failures are recorded by Wallet and must not roll back a successful Membership business transaction.

Provider synchronization therefore follows eventual consistency, not a distributed transaction across Membership and external providers.

### 10.3 Validity flow

A Wallet verification request for a Membership pass must ask Membership for the authoritative current validation result.

Examples:

- active and within valid dates -> valid;
- expired -> expired;
- suspended -> suspended;
- revoked/unpublished according to Membership rules -> invalid/revoked;
- source record missing -> invalid;
- Membership unavailable -> unavailable, not automatically valid.

Wallet may cache presentation data but must not cache authorization-sensitive validity in a way that can incorrectly accept an invalid card.

### 10.4 Deletion behavior

Deleting/revoking a Wallet pass must never delete the Membership card.

Deleting a Membership card, if permitted by Membership business rules, must trigger Wallet revocation/cleanup without creating a reverse dependency that prevents Membership from operating.

## 11. QR verification design

QR is the universal verification method and fallback channel.

The QR contains an HTTPS URL with a high-entropy opaque token. It must not contain the member name, card number, person UUID or other personal data directly.

Example conceptual route:

```text
/index.php?option=com_xdecarowallet&task=verify.open&token=<opaque-token>
```

A SEF route may be added for a cleaner public URL.

Verification sequence:

1. validate token format before lookup;
2. hash token and perform indexed lookup;
3. check token status/revocation;
4. identify Wallet pass;
5. resolve source component/source UUID;
6. call source validation adapter;
7. produce minimal public result;
8. log the verification result according to retention policy.

Public output for a Membership card should expose only the minimum information necessary to establish legitimacy. Default candidate fields:

- valid/invalid status;
- display name;
- issuer organization;
- card number where appropriate;
- expiry date where appropriate.

Any additional personal field requires explicit justification.

The endpoint requires rate limiting/abuse controls, escaped output, no stack traces and no secret/provider diagnostics in public responses.

## 12. Apple Wallet provider

Implement an isolated provider class/service responsible for Apple-specific behavior.

Responsibilities include:

- building pass payload/`pass.json` from the provider-neutral model;
- generating required images/assets;
- localization where supported by the template;
- generating the manifest;
- signing the pass package;
- serving/downloading the `.pkpass` with correct MIME type;
- stable Apple serial identity;
- update registration/web-service support required for live pass updates;
- revocation/invalidation strategy;
- provider diagnostics without exposing private keys or certificate passwords.

Apple certificates, private keys and signing material must never be committed to Git.

Prefer configuration that references credentials stored outside the public web root or supplied through environment/server configuration. Administrator diagnostics may confirm presence, expiry and validity but must not display secret values.

## 13. Google Wallet provider

Implement an isolated provider class/service responsible for Google-specific behavior.

Responsibilities include:

- mapping Xdecaro templates to the appropriate Google Wallet class model;
- creating/updating the per-user object;
- producing the save/add-to-wallet flow;
- updating changed objects without recreating them unnecessarily;
- deactivating/revoking objects according to supported provider semantics;
- recording issuer/object IDs and technical state;
- provider diagnostics without exposing service-account secrets.

Google credentials must never be committed to Git and should be supplied through a secure server-side configuration/reference rather than copied into normal administrator diagnostics.

## 14. Templates

Wallet 1.0 uses controlled templates, not a free-form visual builder.

Initial template:

- `membership_card`

Template responsibilities:

- define required and optional provider-neutral fields;
- define Apple field mapping;
- define Google field mapping;
- define public verification field policy;
- define image requirements and fallbacks;
- define localization labels.

Future templates may include:

- competition accreditation;
- event ticket/accreditation;
- course badge;
- booking credential.

Adding a template must not require changes to existing provider code when the provider supports the necessary generic mapping.

## 15. Administrator UX

Proposed Wallet administrator navigation:

- Dashboard;
- Passes;
- Templates;
- Providers;
- Integrations;
- Verifications;
- Diagnostics;
- Information.

### Dashboard

Show concise operational state:

- total active passes;
- Apple configured/not configured;
- Google configured/not configured;
- recent synchronization failures;
- verification activity summary;
- integration health.

### Passes

Provide:

- search;
- provider filter;
- source component/type filter;
- technical status filter;
- pagination;
- responsive mobile card fallback when tables are too wide;
- actions such as resync, rotate QR token, revoke provider representation and inspect diagnostics, protected by ACL and CSRF.

### Providers

Separate Apple and Google configuration/diagnostics. Never show secret values after save.

### Integrations

Show detected Xdecaro consumers and health, starting with Membership. This is diagnostics/configuration, not ownership of source records.

### Information

Follow the shared Xdecaro Information page structure and diagnostics conventions.

## 16. Frontend/consumer UX

Membership should be able to render a small digital-pass section on a card detail/profile page only when Wallet is installed and the relevant provider is configured.

Candidate actions:

- Add to Apple Wallet;
- Add to Google Wallet;
- Show QR;
- Digital pass status.

Do not show provider buttons that cannot succeed because provider configuration is incomplete.

Actions must remain usable on desktop, tablet and smartphone and must meet keyboard/focus/accessibility requirements.

## 17. Technical status model

Wallet technical status is distinct from source business status.

Suggested Wallet pass statuses:

- `draft` — Wallet record exists but not ready for publication;
- `active` — technical pass is available;
- `sync_pending` — source changed and provider sync is pending/retrying;
- `error` — technical provider synchronization error;
- `revoked` — Wallet representation intentionally revoked;
- `archived` — retained historically but no longer active.

Membership business statuses remain defined by Membership and are not replaced by these values.

Each provider item has its own technical status so Apple may be healthy while Google is temporarily in error.

## 18. Synchronization and retries

Provider synchronization must be idempotent.

Repeated synchronization of an unchanged source record must not create duplicate provider objects.

Use payload hashing and stable provider IDs to avoid unnecessary external calls.

Transient provider failures should be retryable without duplicating objects. A future task/queue integration may handle retries; Wallet 1.0 must at minimum expose a safe manual resync action and a clean service boundary for scheduled retry later.

Do not execute expensive provider synchronization on every frontend page render.

## 19. Security

Required controls include:

- Joomla ACL on all administrator actions;
- CSRF validation on state-changing requests;
- strict input validation;
- escaped output;
- bound database queries;
- no raw secrets in logs or diagnostics;
- no private keys/certificates in Git;
- no personal data embedded directly in QR tokens;
- high-entropy verification tokens;
- constant-time-safe comparison where relevant;
- rate limiting/abuse protection for public verification endpoints;
- authorization checks before showing pass-management actions;
- safe MIME/content-disposition handling for `.pkpass` output;
- no directory traversal through asset/template selection;
- explicit allowlists for template/provider identifiers.

## 20. Privacy and data minimization

Wallet should store only data necessary for pass rendering, provider mapping, verification and diagnostics.

People/Membership remain authoritative for personal data.

Do not copy full People or Membership profiles into Wallet.

Provider payloads should contain only fields required by the visible pass experience.

Verification logs require a documented retention policy before production deployment.

## 21. Error handling

Failures are divided into:

- source unavailable/invalid;
- Wallet configuration invalid;
- provider authentication/certificate error;
- provider API error;
- render/signing error;
- public verification abuse/invalid token.

Administrator errors should be actionable and logged through Joomla logging without leaking secrets.

Membership save/update must not fail solely because Apple or Google is temporarily unavailable after the Membership transaction succeeds.

## 22. Uninstall and data ownership

Uninstalling Wallet may remove Wallet-owned tables and technical provider mappings according to the normal Joomla uninstall policy, but it must never delete Membership cards, People records, Organizations or other source-domain data.

Uninstalling/disabling Wallet must leave source components functional.

Provider-side cleanup/revocation behavior must be explicit and must not happen destructively without administrator intent where the external provider may retain user-visible passes.

## 23. Versioning and compatibility

Use Semantic Versioning from `1.0.0` onward.

Current ecosystem test baseline is Joomla 6.x. Code should avoid unnecessary incompatibilities with Joomla 4/5 where technically possible and where doing so does not introduce fragile shims.

Release metadata must remain coherent across manifests, package manifest, changelog, update server, SQL updates, Git tag/release and installable ZIP.

No two different builds may share the same version number.

## 24. Delivery phases

### Phase A — foundation

- repository/package/component skeleton;
- database schema;
- ACL;
- provider-neutral pass service;
- Membership card UUID prerequisite;
- Membership adapter/integration;
- secure QR token generation;
- public verification endpoint;
- administrator Passes/Diagnostics base;
- tests for source linking, validity and security.

### Phase B — Apple Wallet

- fixed `membership_card` template;
- pass generation/signing;
- add/download flow;
- provider mapping persistence;
- update registration/web service;
- synchronization and diagnostics;
- tests with non-production test credentials/certificates where available.

### Phase C — Google Wallet

- issuer configuration;
- class/object mapping;
- add-to-wallet flow;
- update/revocation lifecycle;
- synchronization and diagnostics;
- tests with non-production/test issuer setup where available.

### Phase D — 1.0 stabilization

- integration regression tests with Membership;
- clean install/update tests;
- ACL/CSRF/security review;
- responsive desktop/tablet/mobile review;
- light/dark mode review;
- accessibility review;
- Joomla/PHP error and JavaScript Console checks;
- package build validation;
- documentation and Information page diagnostics.

### Future — NFC/contactless

NFC/contactless support is intentionally deferred until QR, Apple and Google pass lifecycle are stable.

Future work may include Apple contactless/VAS-related capabilities, Google Smart Tap and compatible reader integration. Hardware/provisioning requirements must be designed separately and must not change Membership ownership of the underlying card.

## 25. Test strategy

Minimum automated/static coverage should include:

- unique source-to-pass mapping;
- UUID generation and immutability;
- idempotent upsert/sync;
- payload hash behavior;
- secure token generation and hash lookup;
- token rotation/revocation;
- source validation adapter results;
- invalid/missing source behavior;
- provider failure isolation;
- ACL and CSRF for state changes;
- input validation and output escaping;
- install/update SQL preservation;
- package manifest integrity.

Manual/runtime validation should include:

- Membership creates/updates a card with Wallet installed;
- Membership still works with Wallet disabled/uninstalled;
- valid, expired, suspended and revoked verification results;
- Apple pass add/open/update where credentials permit;
- Google pass add/open/update where credentials permit;
- mobile QR flow;
- administrator responsive views;
- light and dark mode;
- accessibility/keyboard focus;
- PHP errors/warnings/deprecations;
- JavaScript Console;
- provider diagnostics without exposed secrets.

## 26. Acceptance criteria for Wallet 1.0

Wallet 1.0 is acceptable when:

1. Membership remains the authoritative owner of Membership cards.
2. Wallet can map one Membership card UUID to one Wallet pass without duplicating business records.
3. Wallet absence does not break Membership.
4. QR verification uses an opaque secure token and returns authoritative Membership validity.
5. Apple Wallet pass generation/add flow works with valid provider configuration.
6. Google Wallet add flow works with valid provider configuration.
7. Source changes synchronize idempotently to configured providers.
8. Provider failures do not corrupt or roll back Membership state.
9. Secrets are not committed, rendered or logged.
10. Administrator UI is usable on desktop, tablet and smartphone in light/dark modes.
11. ACL, CSRF, validation, escaping and database protections are verified.
12. Install/update/uninstall behavior preserves all source-domain data.
13. NFC remains a future optional layer and is not required for 1.0 operation.

## 27. Explicit architectural decisions

The following decisions are considered part of this design:

- Wallet is a separate Xdecaro product, not a feature embedded entirely in Core.
- Membership cards stay in Membership.
- Wallet stores digital-pass state, not membership business truth.
- Core receives no Wallet-specific abstraction in the first implementation.
- Integration is optional and failure-isolated.
- Stable source UUIDs are preferred over database IDs or card numbers for cross-component mapping.
- QR is implemented from the first release and remains the universal fallback.
- Apple and Google are isolated providers behind the Wallet service boundary.
- A fixed Membership template is used before any generic visual builder.
- NFC/contactless is deferred until the normal digital-pass lifecycle is stable.
