# Xdecaro Wallet Phase D — Stabilization and 1.0 Release Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stabilize Wallet with Membership, Apple and Google providers, close security/privacy/accessibility gaps, verify clean install/update/uninstall and publish the first production-ready 1.0.0 package only after all acceptance criteria pass.

**Architecture:** No new product scope is introduced here. This phase verifies the boundaries already implemented: Membership owns cards and validity; Wallet owns digital representations; Apple/Google are isolated providers; QR remains the universal fallback; NFC/contactless remains deferred.

**Tech Stack:** Joomla test runtime(s), PHP 8.x, MySQL/MariaDB, browser/manual responsive testing, Xdecaro package build/release workflow, GitHub Actions/update server.

**Spec:** `docs/superpowers/specs/2026-09-29-wallet-architecture-design.md`

## Global Constraints

- Phase A, B and C completion gates must be green before release work.
- No NFC/Smart Tap/VAS implementation enters 1.0 stabilization.
- No provider credential/private key may exist in repository, package ZIP, logs or diagnostics.
- Membership must remain operational when Wallet is disabled/uninstalled.
- All final artifacts/version metadata use exactly `1.0.0` for the first public Wallet release.
- Different code must never be distributed with the same version.

## Review Focus

- Upgrade from Phase A/B/C development schemas to final 1.0 without data loss or duplicate provider rows.
- Uninstall/reinstall Wallet while Membership cards persist and remain unchanged.
- Provider credential errors in production-like configuration must not leak paths/passwords/private material.
- Public verification response under malformed/high-volume requests must fail safely and remain responsive.
- Mobile/admin UX in dark/light mode must remain usable with long names/card numbers/error messages.

---

### Task 1: Complete security, privacy and data-retention review

**Files:**
- Review/modify all Wallet controllers/services/repositories/provider diagnostics
- Create/update: `wallet/docs/security.md`, `wallet/docs/privacy-retention.md`
- Create tests: `wallet/tests/security-contract.php`, `wallet/tests/privacy-contract.php`

**Interfaces:**
- Public verification stores only pass UUID, timestamp, result and channel by default.
- Secret-bearing configuration is reference-only/masked after save.

- [ ] Verify every state-changing administrator action requires ACL and Joomla CSRF token.
- [ ] Verify public verification validates token format before lookup, uses indexed hashed lookup and returns no stack/provider diagnostics.
- [ ] Search repository/package build output for private keys, certificates, service-account JSON, passwords, raw QR tokens and debug dumps; expected zero hits except documented test fixtures containing no real secrets.
- [ ] Document default verification log retention policy and administrator cleanup mechanism before production deployment.
- [ ] Verify SQL is bound/escaped and template/provider identifiers are allowlisted.
- [ ] Commit `security: harden Wallet 1.0`.

---

### Task 2: Complete Membership/Wallet regression matrix

**Files:**
- Create/update runtime regression scripts in both repositories
- Update integration documentation

- [ ] Clean install Membership without Wallet and verify cards, DCL card paths, renewals/transfers and normal Membership administration.
- [ ] Install Wallet afterward and verify existing cards can receive stable Wallet passes without changing Membership card numbers/statuses.
- [ ] Upgrade a pre-UUID Membership database and verify row counts/data/relationships are preserved.
- [ ] Disable Wallet component/plugin and save/edit Membership cards; expected normal Membership operation.
- [ ] Uninstall Wallet and verify no Membership/People/Organizations rows are deleted.
- [ ] Reinstall Wallet and resync a card; no duplicate Membership card and no stale source mapping assumptions.
- [ ] Commit `test: verify Membership Wallet isolation`.

---

### Task 3: Complete Apple/Google/QR functional matrix

**Files:**
- Update provider/verification tests and diagnostics only as required by discovered issues.

- [ ] For one active Membership card, verify QR, Apple and Google representations refer to the same source card UUID/pass identity.
- [ ] Transition source through active, suspended, expired and revoked; verify QR returns live Membership truth and Apple/Google update without new source records.
- [ ] Test Apple certificate/APNs outage and Google 401/403/429/5xx failures; Wallet records retryable technical errors without corrupting source state.
- [ ] Rotate QR token and verify old token invalid/new token valid while Apple/Google object/serial identity remains stable.
- [ ] Re-run provider idempotency tests after multiple manual resyncs.
- [ ] Commit `test: verify Wallet provider consistency`.

---

### Task 4: Complete administrator/frontend UX, responsive, dark mode and accessibility review

**Files:**
- Modify Wallet media CSS/JS/templates/language strings only for verified issues
- Create/update UI contract tests

- [ ] Desktop: Dashboard, Passes, Providers, Integrations, Verifications, Diagnostics, Information and consumer controls have coherent toolbar/layout/spacing.
- [ ] Tablet/mobile: no horizontal overflow; wide tables use responsive behavior/card fallback; buttons remain touch-friendly; long names/numbers wrap safely.
- [ ] Light/dark mode: backgrounds, text, borders, inputs, badges, dropdowns, modals and focus state maintain readable contrast without light-only hardcoding.
- [ ] Keyboard/accessibility: visible focus, semantic labels, meaningful accessible button names, no state by color alone, correct modal focus behavior.
- [ ] Reduced-motion behavior does not hide or prevent actions.
- [ ] Browser Console contains no Wallet errors/warnings from normal flows.
- [ ] Commit `fix: polish Wallet 1.0 user experience`.

---

### Task 5: Verify install/update/uninstall/package integrity

**Files:**
- Finalize manifests, SQL update chain, build scripts, updater XML, changelog, README, Information diagnostics
- Final `VERSION` = `1.0.0`

- [ ] Build component/plugin/package ZIPs using the Xdecaro package convention; final package is directly installable by Joomla.
- [ ] Inspect ZIP: no extra parent directory, backups, temp files, `.DS_Store`, IDE files, logs, test secrets or nested release ZIPs.
- [ ] Clean-install `pkg_xdecarowallet_1.0.0.zip` on clean Joomla runtime.
- [ ] Test update from the latest pre-release development schema/package to 1.0.0 preserving pass/provider/token/registration data.
- [ ] Test uninstall: Wallet-owned tables/extensions removed according to policy while Membership data is preserved.
- [ ] Verify package/component/plugin/update-server/changelog versions all equal `1.0.0`.
- [ ] Commit `build: prepare Wallet 1.0.0`.

---

### Task 6: Final runtime and release gate

**Files:**
- Release metadata/tag/release notes only after all prior steps are green.

- [ ] Run complete automated/static test suites for Wallet and changed Membership baseline; expected zero failures.
- [ ] Check PHP error log for warnings/notices/deprecations introduced by Wallet/Membership integration; expected zero actionable errors.
- [ ] Check JavaScript Console on changed/admin/frontend surfaces; expected zero errors.
- [ ] Verify provider diagnostics never display secret values and clearly distinguish configured/not-configured/error states.
- [ ] Verify QR verification over HTTPS on production-like routing.
- [ ] Verify Apple/Google add controls are hidden/disabled when corresponding provider is not usable.
- [ ] Verify final SHA-256 of release package and record it in release process.
- [ ] Create Git tag/release `v1.0.0`, publish the exact tested ZIP and updater metadata; never rebuild different code under the same version.
- [ ] Commit/tag only after evidence from all checks is captured.

## Wallet 1.0 Release Gate

Release only when all approved architecture acceptance criteria are met: Membership remains authoritative and independent; Wallet maps one stable source to one pass; QR is secure and authoritative; Apple signed/download/update lifecycle works; Google GenericClass/Object/save/update lifecycle works; provider failures are isolated; credentials remain secret; admin/frontend are responsive/accessibile in light/dark mode; install/update/uninstall preserve source-domain data. NFC/contactless remains a separately designed future phase.
