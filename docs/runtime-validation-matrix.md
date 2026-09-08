# Xdecaro ecosystem runtime validation matrix

This matrix is the final gate before any Core 1.2.0 public API expansion.

A green repository workflow is necessary but is not proof of Joomla runtime compatibility. Each product must be exercised on a real Joomla installation for the versions it claims to support.

## Products in scope

- Core — `pkg_xdecarocore`
- Forms — `pkg_decaroforms`
- Courses — `pkg_decarocourses`
- Competitions — `pkg_decarodcl`
- Documents — `pkg_decarodocuments`
- Membership — `pkg_decaromembership`
- Events — `pkg_decaroevents`
- Editor — `pkg_decaroeditor`
- Finance — `pkg_decarofinance`
- Protocol — `pkg_decaroprotocol`
- Draw — `pkg_decarodraw`
- Organizations — `pkg_decaroorganizations`
- People — `pkg_decaropeople`
- Inventory — `pkg_decaroinventory`
- Resources — `pkg_decaroresources`

Communication and Bookings join this matrix when their repositories exist.

## Required runtime checks for every package

Record PASS / FAIL / N/A with Joomla version, PHP version, package version and date.

| Check | Required result |
| --- | --- |
| Clean installation | Package installs without warnings/fatals and registers expected extensions only |
| Upgrade | Existing data/configuration survive a supported upgrade |
| Uninstall | Follows the product's documented preservation/deletion policy |
| Core detection | Correct Core version and API availability are reported |
| Missing Core | Optional consumers fall back cleanly; hard dependencies fail before partial installation |
| Administrator access | `core.manage` and product ACL are enforced server-side |
| State-changing requests | Joomla CSRF token validation is enforced |
| Input validation | Invalid IDs/states/values are rejected server-side |
| Database | Tables, indexes, foreign keys and update state are correct; no destructive normal update |
| Cross-product boundary | No direct reads/writes of another product's private tables |
| Web assets | Core assets load through WAM/AssetService only when requested |
| Shared UI scope | Core primitives are inside `.xdecaro-scope` and do not leak globally |
| JavaScript | Browser console has no relevant errors or duplicate listeners/AJAX |
| PHP runtime | PHP log has no warnings, notices promoted to errors, deprecations that break supported Joomla, or fatals |
| Desktop | Primary affected views render and operate correctly |
| Tablet | No overflow/control loss in primary affected views |
| Smartphone | Primary actions remain reachable and usable |
| Light mode | Contrast, borders, badges and controls remain legible |
| Dark mode | No hard-coded light backgrounds/text conflicts with Core tokens |
| Accessibility basics | Labels, keyboard focus and semantic controls remain usable where applicable |

## Product-specific runtime focus

### Core
- install library + system plugin through `pkg_xdecarocore`;
- WAM registry available without global CSS injection;
- `AssetService::useFoundation()` and `useComponents()` can be called repeatedly without duplicate registration failures;
- `EntityReference` / `RelationReference` validation remains stable.

### Forms
- Information/Diagnostics Core UI and fallback;
- Builder/submissions/email/payment paths unchanged by Core integration;
- current Builder migration work must be tested separately from the Core baseline.

### Courses
- Information Core UI/fallback;
- course/edition/enrolment/lesson/attendance/evaluation behavior unchanged.

### Competitions
- Core diagnostics/reference integration;
- legacy technical identity `com_decarodcl` remains intact;
- no regression in competitions/teams/matches.

### Documents
- package refuses installation when Core < 1.1.0;
- private storage can be created on the target hosting;
- upload MIME/extension validation;
- download ACL/view-level check;
- replace/delete file lifecycle and rollback behavior;
- no private storage path exposed in diagnostics.

### Membership
- Core UI/fallback;
- membership records/workflows remain independent of People until an explicit public integration is implemented.

### Events
- Core-first install;
- event/session/registration capacity and ownership validation;
- registration/check-in state changes protected by ACL/CSRF.

### Editor
- Core 1.1 UI optional fallback;
- Joomla editor plugin loads correctly;
- canvas/block/media/history behavior remains local to Editor;
- test supported Joomla/PHP matrix exactly as declared by the release.

### Finance
- ledger/budget/deposit writes are transactional where required;
- idempotency guards prevent duplicate financial writes;
- no Competitions private-table dependency.

### Protocol
- numbering/register invariants survive concurrent normal usage;
- protocol records remain separate from Documents storage and Communication delivery.

### Draw
- no writes to `#__dcl_*`;
- execution/publish ACL boundaries;
- later live-draw engine tests belong to Draw, not Core.

### Organizations
- hierarchy integrity and cycle prevention when hierarchy editing is implemented;
- organization references do not grant access to linked product data.

### People
- person records remain separate from Joomla users and Membership;
- personal data is not exposed through diagnostics/logging;
- ACL around personally identifiable data is tested explicitly.

### Inventory
- stock movement history is append/audit safe;
- quantity changes cannot bypass domain validation;
- no booking/accounting logic is introduced implicitly.

### Resources
- capacity/resource definitions remain independent from booking state;
- no Inventory stock mutation through private-table coupling.

## Version claims

Do not claim Joomla 4, 5 or 6 compatibility because a manifest regex allows it. Mark a Joomla major as supported only after the applicable package has completed the runtime checks above on that major.

## Completion rule

Core 1.2.0 work may start only when:

1. all currently released/consumed products needed for the target deployment have a recorded runtime result;
2. failures affecting Core contracts or shared UI are fixed without domain leakage into Core;
3. at least two real consumers demonstrate the same domain-neutral missing capability before a new public Core API is proposed;
4. Communication and Bookings, once created, adopt the same boundary rather than forcing Core to know their domains.
