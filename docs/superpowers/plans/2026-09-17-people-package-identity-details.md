# People Package and Identity Details Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Release People 1.3.0 as canonical `pkg_people` and add a narrow public provider permission that exposes only birth date/place for identity disambiguation.

**Architecture:** Keep `com_xdecaropeople`, database tables and full `people.view_sensitive` behavior unchanged. Add `people.view_identity_details` and provider methods/flags that expose only `birth_date` and `birth_place`; package migration recognizes canonical `pkg_core` and legacy `pkg_xdecarocore` during the transition.

**Tech Stack:** Joomla 6.1.3, PHP 8.3, Joomla ACL, public `PersonProviderService`, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-17-ecosystem-package-naming-design.md`

## Global Constraints

- Canonical package is `pkg_people`, `<packagename>people</packagename>`, ZIP `pkg_people_1.3.0.zip`.
- Keep `com_xdecaropeople`, `#__xdecaropeople_people`, namespaces and public entity references unchanged.
- New limited ACL action is `people.view_identity_details`.
- Limited provider output contains only normal public columns plus `birth_date` and `birth_place`; it must not expose disability, tax, residence, notes or document references.
- Existing `people.view_sensitive` continues to expose the current sensitive profile contract.
- Prefer runtime `xdecaro\\Core\\Version`; package fallback recognizes `pkg_core` first and `pkg_xdecarocore` temporarily.

---

### Task 1: TDD contract for limited identity details

**Files:**
- Create: `tests/person-provider-identity-details-contract.php`
- Modify: `.github/workflows/build.yml`
- Modify: `.github/workflows/release.yml`

- [ ] **Step 1: Write failing contract**

Require `people.view_identity_details`, `birth_date`, `birth_place`, and a dedicated provider path that does not reuse the complete sensitive column list.

```php
$access = file_get_contents($root . '/component/admin/access.xml');
$provider = file_get_contents($root . '/component/admin/src/Service/PersonProviderService.php');
foreach (['people.view_identity_details', 'birth_date', 'birth_place'] as $needle) {
    if (!str_contains($access . $provider, $needle)) exit(1);
}
if (!str_contains($provider, 'identityDetails')) exit(1);
```

- [ ] **Step 2: Verify RED**

Run: `php tests/person-provider-identity-details-contract.php`
Expected: FAIL.

- [ ] **Step 3: Add test to CI/release validation**

- [ ] **Step 4: Commit test**

### Task 2: Add narrow ACL and provider contract

**Files:**
- Modify: `component/admin/access.xml`
- Modify: `component/admin/language/en-GB/com_xdecaropeople.ini`
- Modify: `component/admin/language/it-IT/com_xdecaropeople.ini`
- Modify: `component/admin/src/Service/PersonProviderService.php`
- Test: `tests/person-provider-identity-details-contract.php`
- Test: `tests/person-provider-sensitive-contract.php`

- [ ] **Step 1: Add ACL action**

```xml
<action name="people.view_identity_details" title="COM_XDECAROPEOPLE_ACTION_VIEW_IDENTITY_DETAILS"/>
```

Translations:

```ini
COM_XDECAROPEOPLE_ACTION_VIEW_IDENTITY_DETAILS="View identity details"
COM_XDECAROPEOPLE_ACTION_VIEW_IDENTITY_DETAILS="Visualizza dati identificativi"
```

- [ ] **Step 2: Add provider API without weakening sensitive ACL**

Add methods such as:

```php
public function searchPeopleIdentityDetails(array $filters = [], int $limit = 50): array
public function getPersonIdentityDetails(int|string $id): ?array
```

Both must authorize `core.manage`/`core.admin` as today, then require `people.view_identity_details` OR `people.view_sensitive` OR component `core.admin` for the limited fields.

Select normal public columns plus only:

```php
['p.birth_date', 'p.birth_place']
```

Do not include the existing full sensitive column set.

- [ ] **Step 3: Run contracts**

```bash
php tests/person-provider-identity-details-contract.php
php tests/person-provider-sensitive-contract.php
```

Expected: PASS.

- [ ] **Step 4: Commit**

### Task 3: Canonicalize People package identity

**Files:**
- Create: `package/pkg_people.xml`
- Modify: `package/script.php`
- Modify: `build/build.sh`
- Create: `updates/pkg_people.xml`
- Delete after replacement: `package/pkg_xdecaropeople.xml`
- Delete after replacement: `updates/pkg_xdecaropeople.xml`
- Modify: `VERSION`

- [ ] **Step 1: Set version `1.3.0`**

- [ ] **Step 2: Canonical manifest**

Use `<packagename>people</packagename>` and update server `updates/pkg_people.xml`; keep child `com_xdecaropeople.zip` unchanged.

- [ ] **Step 3: Installer/dependency migration**

Rename installer class to `pkg_peopleInstallerScript`. Core detection order:

1. `xdecaro\\Core\\Version` runtime API.
2. package `pkg_core`.
3. temporary fallback `pkg_xdecarocore`.

After successful canonical install, retire legacy `pkg_xdecaropeople` package registration only after `com_xdecaropeople` is owned by canonical `pkg_people`; do not uninstall the component.

- [ ] **Step 4: Build canonical ZIP**

`dist/pkg_people_1.3.0.zip` must contain `pkg_people.xml`, `script.php`, `com_xdecaropeople.zip`.

- [ ] **Step 5: Update feed/release names**

Use `<element>pkg_people</element>` and canonical GitHub release URL.

### Task 4: Runtime migration and ACL verification

**Files:**
- Modify: `.github/workflows/build.yml`
- Add/modify runtime workflow as needed.

- [ ] **Step 1: Install Core canonical package and legacy People 1.2.18**
- [ ] **Step 2: Upgrade with current `pkg_people_1.3.0.zip`**
- [ ] **Step 3: Assert exactly one canonical People package and one unchanged component**
- [ ] **Step 4: Seed a person with birth date/place**
- [ ] **Step 5: Verify user with `people.view_identity_details` gets birth date/place but not tax/residence/disability fields**
- [ ] **Step 6: Verify user without limited/full sensitive permission cannot receive birth date/place**
- [ ] **Step 7: Verify full sensitive permission still returns existing sensitive contract**

### Task 5: Release alignment

**Files:**
- Modify: `.github/workflows/release.yml`
- Modify: `README.md`
- Modify/update package-specific workflow currently named `people-1.2.18-package.yml`

- [ ] **Step 1: Rename/update package workflow for 1.3.0 semantics**
- [ ] **Step 2: Publish `pkg_people_${V}.zip`, checksum and `updates/pkg_people.xml`**
- [ ] **Step 3: Verify published update resource resolves and checksum matches**
- [ ] **Step 4: Require green CI before merge/release**
