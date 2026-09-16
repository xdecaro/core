# Organizations Package and People Identity Details Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Consolidate the current Members fixes into Organizations 1.1.0, migrate package identity to `pkg_organizations`, and consume People’s narrow identity-detail provider so member search shows birth date/place without full sensitive access.

**Architecture:** Continue from branch `fix/members-tab-persistence-1.0.26` / PR #32, retaining Members-tab persistence, single-appointment deletion and birth-detail rendering already implemented. Replace the temporary `searchPeople(..., true)` strategy with People’s dedicated limited identity-details API and canonicalize the Joomla package layer.

**Tech Stack:** Joomla 6.1.3, PHP 8.3, JavaScript autocomplete, People public provider, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-17-ecosystem-package-naming-design.md`

## Global Constraints

- Canonical package is `pkg_organizations`; child remains `com_xdecaroorganizations`.
- Preserve `#__xdecaroorganizations_appointments`, appointment UUID/person UUID references and all existing organization data.
- Keep Members tab active after save/end/delete.
- People birth date/place are display-only and never stored in appointments.
- Organizations must never access People private tables.
- Use the limited People identity-detail provider; fallback to public name-only search if unavailable/not authorized.
- Release as 1.1.0 to combine the backward-compatible Members feature set and package standardization.

---

### Task 1: Update People integration contract to require limited API

**Files:**
- Modify: `tests/appointments-people-disambiguation-contract.php`
- Modify: `component/admin/src/Service/PeopleIntegrationService.php`

- [ ] **Step 1: Change test to RED**

Require Organizations to call `searchPeopleIdentityDetails` when available and prohibit using `searchPeople(..., true)` for autocomplete.

```php
if (!str_contains($service, 'searchPeopleIdentityDetails')) exit(1);
if (preg_match('/searchPeople\([^;]+true/s', $service)) exit(1);
```

- [ ] **Step 2: Run test and verify FAIL**

Run: `php tests/appointments-people-disambiguation-contract.php`
Expected: FAIL on current branch.

- [ ] **Step 3: Implement provider preference/fallback**

Algorithm:

```php
if (method_exists($provider, 'searchPeopleIdentityDetails')) {
    try { return $provider->searchPeopleIdentityDetails(['search' => $search], $limit); }
    catch (Throwable) { /* fall through */ }
}
return $provider->searchPeople(['search' => $search], $limit, false);
```

Never request the full sensitive People profile just for autocomplete.

- [ ] **Step 4: Run People/appointments contracts**

Expected: PASS.

### Task 2: Preserve current Members UI behavior

**Files:**
- Keep/verify: `component/media/js/organization-edit.js`
- Keep/verify: `component/admin/src/Controller/AppointmentController.php`
- Test: `tests/members-tab-persistence-contract.php`
- Test: `tests/appointments-ui-contract.php`
- Test: `tests/appointments-backend-contract.php`

- [ ] **Step 1: Verify `reloadMembersTab()` always adds `activeTab=members` after save/end/delete**
- [ ] **Step 2: Verify autocomplete renders name plus formatted `DD/MM/YYYY · birth place` when present**
- [ ] **Step 3: Verify controller sends only `uuid`, `name`, `birth_date`, `birth_place`**
- [ ] **Step 4: Verify appointment save persists person UUID/name snapshot but not birth data**
- [ ] **Step 5: Run existing Members tests**

### Task 3: Canonicalize Organizations package

**Files:**
- Create: `package/pkg_organizations.xml`
- Modify: `package/script.php`
- Modify: `build/build.sh`
- Create: `updates/pkg_organizations.xml`
- Delete after replacement: `package/pkg_xdecaroorganizations.xml`
- Delete after replacement: `updates/pkg_xdecaroorganizations.xml`
- Modify: `VERSION`
- Modify: `component/xdecaroorganizations.xml`
- Modify: `component/media/joomla.asset.json`

- [ ] **Step 1: Set version 1.1.0 consistently**
- [ ] **Step 2: Set `<packagename>organizations</packagename>` and canonical update feed**
- [ ] **Step 3: Rename installer class to `pkg_organizationsInstallerScript`**
- [ ] **Step 4: Core dependency detection prefers runtime API, then `pkg_core`, then legacy `pkg_xdecarocore`**
- [ ] **Step 5: Retire legacy `pkg_xdecaroorganizations` package registration only after canonical package owns `com_xdecaroorganizations`**
- [ ] **Step 6: Build `dist/pkg_organizations_1.1.0.zip` with unchanged component child**

### Task 4: Runtime integration with canonical Core + People

**Files:**
- Modify: `.github/workflows/appointments-runtime.yml`
- Modify: `.github/workflows/build.yml`

- [ ] **Step 1: Install canonical `pkg_core`**
- [ ] **Step 2: Install canonical `pkg_people`**
- [ ] **Step 3: Install legacy Organizations 1.0.25/1.0.26 test state and upgrade to `pkg_organizations_1.1.0`**
- [ ] **Step 4: Assert one canonical Organizations package, one component, preserved appointment table/data**
- [ ] **Step 5: Create two People records with same/similar display name but different birth details**
- [ ] **Step 6: Grant limited People identity-details permission to test administrator and assert search JSON contains birth date/place**
- [ ] **Step 7: Remove limited permission and assert search still works name-only**
- [ ] **Step 8: Re-run appointment save/end/delete flow and ensure active Members tab behavior remains covered**

### Task 5: Release/update metadata and PR #32 consolidation

**Files:**
- Modify: `.github/workflows/release.yml`
- Modify: `README.md`
- Modify: package/update metadata and tests that reference 1.0.26/pkg_xdecaroorganizations.

- [ ] **Step 1: Retitle PR #32 for Organizations 1.1.0 canonical package + Members/People improvements**
- [ ] **Step 2: Publish canonical artifact name `pkg_organizations_${V}.zip`**
- [ ] **Step 3: Release notes mention Members-tab persistence, single-active-appointment delete, limited birth-detail disambiguation and canonical package migration**
- [ ] **Step 4: Verify deterministic build, PHP syntax, all contract tests and Joomla 6.1.3 runtime**
- [ ] **Step 5: Keep PR Draft until manual Joomla test confirms People results and package migration**
