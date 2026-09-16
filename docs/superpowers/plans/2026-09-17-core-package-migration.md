# Core Package Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate Core from `pkg_xdecarocore` to canonical `pkg_core` while preserving `lib_xdecarocore`, `com_xdecarocore`, `plg_system_xdecarocore`, installed state and Joomla update capability.

**Architecture:** The package identity changes but child extension identities and public runtime APIs do not. A migration-safe installer retires the legacy package registration only after the canonical package owns the existing children. Build/release/update metadata become canonical and runtime CI proves an install over Core 2.0.1.

**Tech Stack:** Joomla 6.1.3, PHP 8.3, Bash, Python 3, GitHub Actions, MariaDB runtime CI.

**Spec:** `docs/superpowers/specs/2026-09-17-ecosystem-package-naming-design.md`

## Global Constraints

- Canonical package identity is `pkg_core`; `<packagename>` is `core`.
- Keep `lib_xdecarocore`, `com_xdecarocore`, `plg_system_xdecarocore`, `xdecaro\\Core`, service identifiers and database state unchanged.
- Joomla updates use `updates/pkg_core.xml` and `pkg_core_<version>.zip`.
- Preserve existing data/configuration and never uninstall child extensions during package-identity migration.
- PHP minimum remains 8.3 and current Core 2.x Joomla target remains Joomla 6.
- Use Core 2.1.0 for the first canonical-package release.

---

### Task 1: Add a failing canonical-package contract

**Files:**
- Create: `tests/package-naming-migration.php`
- Modify: `build/build.sh`

**Interfaces:**
- Consumes: package manifest/build/update-feed files.
- Produces: a contract requiring `pkg_core`, `package/pkg_core/pkg_core.xml`, `updates/pkg_core.xml`, canonical ZIP naming and legacy compatibility only in migration code.

- [ ] **Step 1: Write the failing test**

```php
<?php
$root = dirname(__DIR__);
$manifest = (string) file_get_contents($root . '/package/pkg_core/pkg_core.xml');
$build = (string) file_get_contents($root . '/build/build.py');
$feed = (string) file_get_contents($root . '/updates/pkg_core.xml');

foreach ([
    '<packagename>core</packagename>' => $manifest,
    'updates/pkg_core.xml' => $manifest,
    'pkg_core_' => $build,
    '<element>pkg_core</element>' => $feed,
] as $needle => $haystack) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, "Missing canonical Core package contract: {$needle}\n");
        exit(1);
    }
}
```

- [ ] **Step 2: Run it and verify RED**

Run: `php tests/package-naming-migration.php`
Expected: FAIL because canonical package files do not exist yet.

- [ ] **Step 3: Add the test to `build/build.sh`**

Add:

```bash
php "$ROOT/tests/package-naming-migration.php"
```

- [ ] **Step 4: Commit**

```bash
git add tests/package-naming-migration.php build/build.sh
git commit -m "test: require canonical Core package identity"
```

### Task 2: Rename package source/build/update metadata

**Files:**
- Create: `package/pkg_core/pkg_core.xml`
- Create: `package/pkg_core/script.php`
- Create: `updates/pkg_core.xml`
- Modify: `build/build.py`
- Modify: `build/build.sh`
- Modify: `build/update-feed-checksum.php`
- Delete after replacement: `package/pkg_xdecarocore/pkg_xdecarocore.xml`
- Delete after replacement: `package/pkg_xdecarocore/script.php`
- Delete after replacement: `updates/pkg_xdecarocore.xml`

**Interfaces:**
- Produces canonical release artifact `dist/pkg_core_<version>.zip` containing `pkg_core.xml` plus the three unchanged child ZIPs.

- [ ] **Step 1: Create canonical manifest**

Use:

```xml
<extension type="package" method="upgrade">
  <name>Core</name>
  <packagename>core</packagename>
  <version>2.1.0</version>
  <scriptfile>script.php</scriptfile>
  <blockChildUninstall>true</blockChildUninstall>
  <files>
    <file type="library" id="xdecaro/core">lib_xdecarocore.zip</file>
    <file type="component" id="com_xdecarocore">com_xdecarocore.zip</file>
    <file type="plugin" id="xdecarocore" group="system">plg_system_xdecarocore.zip</file>
  </files>
  <updateservers>
    <server type="extension" priority="1" name="Core Updates">https://raw.githubusercontent.com/xdecaro/core/main/updates/pkg_core.xml</server>
  </updateservers>
  <changelogurl>https://raw.githubusercontent.com/xdecaro/core/main/updates/changelog.xml</changelogurl>
</extension>
```

Keep existing author/license/target metadata.

- [ ] **Step 2: Update deterministic build names**

Change package source/output from `pkg_xdecarocore` to `pkg_core`; leave all child ZIP names unchanged.

- [ ] **Step 3: Create canonical update feed**

Require:

```xml
<element>pkg_core</element>
<version>2.1.0</version>
<downloadurl type="full" format="zip">https://github.com/xdecaro/core/releases/download/v2.1.0/pkg_core_2.1.0.zip</downloadurl>
```

Retain stable tag, Joomla target, PHP minimum and SHA-256 field.

- [ ] **Step 4: Update checksum helper**

Use `dist/pkg_core_${version}.zip` and `updates/pkg_core.xml`.

- [ ] **Step 5: Run contract/build**

Run:

```bash
php tests/package-naming-migration.php
bash build/build.sh
```

Expected: PASS and `dist/pkg_core_2.1.0.zip` exists.

- [ ] **Step 6: Commit**

```bash
git add package build updates tests VERSION
git commit -m "feat: migrate Core package identity to pkg_core"
```

### Task 3: Implement safe legacy package-registration retirement

**Files:**
- Modify: `package/pkg_core/script.php`
- Test: `tests/package-naming-migration.php`

**Interfaces:**
- Canonical package element: `pkg_core`.
- Legacy package element: `pkg_xdecarocore`.
- Child identities remain unchanged.

- [ ] **Step 1: Extend the failing contract**

Require the installer to contain both canonical and legacy package constants and an explicit ownership check before retirement.

```php
foreach (['pkg_core', 'pkg_xdecarocore', 'package_id'] as $needle) {
    if (!str_contains($installer, $needle)) {
        exit(1);
    }
}
```

- [ ] **Step 2: Verify RED**

Run: `php tests/package-naming-migration.php`
Expected: FAIL until migration logic exists.

- [ ] **Step 3: Add migration logic**

In `postflight`, after normal Core postflight work:

1. Resolve current canonical package `extension_id` where `type='package' AND element='pkg_core'`.
2. Verify each child extension (`library xdecaro/core`, `component com_xdecarocore`, `plugin system/xdecarocore`) has `package_id` equal to the canonical package ID.
3. Only then retire the legacy `pkg_xdecarocore` package row and its update-site association without invoking package uninstall.
4. Delete the legacy package manifest file from `JPATH_ADMINISTRATOR . '/manifests/packages/pkg_xdecarocore.xml'` only after DB retirement succeeds.
5. If any ownership check fails, keep the legacy package record and emit a warning; never remove a child.

Use Joomla Database API with bound values for runtime IDs/elements.

- [ ] **Step 4: Run PHP syntax and contract**

```bash
php -l package/pkg_core/script.php
php tests/package-naming-migration.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add package/pkg_core/script.php tests/package-naming-migration.php
git commit -m "feat: retire legacy Core package registration safely"
```

### Task 4: Add Joomla runtime migration coverage

**Files:**
- Modify: `.github/workflows/runtime-smoke.yml`

- [ ] **Step 1: Add runtime assertions**

Install released `pkg_xdecarocore_2.0.1.zip`, then install current `pkg_core_2.1.0.zip`. Assert:

```sql
SELECT COUNT(*) FROM jos_extensions WHERE type='package' AND element='pkg_core'; -- 1
SELECT COUNT(*) FROM jos_extensions WHERE type='package' AND element='pkg_xdecarocore'; -- 0
SELECT COUNT(*) FROM jos_extensions WHERE type='library' AND element='xdecaro/core'; -- 1
SELECT COUNT(*) FROM jos_extensions WHERE type='component' AND element='com_xdecarocore'; -- 1
SELECT COUNT(*) FROM jos_extensions WHERE type='plugin' AND folder='system' AND element='xdecarocore'; -- 1
```

Also assert all three children have the canonical Core package ID in `package_id`, and plugin remains enabled.

- [ ] **Step 2: Keep normal clean-install matrix using `pkg_core_<version>.zip`**

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/runtime-smoke.yml
git commit -m "test: cover legacy-to-canonical Core package migration"
```

### Task 5: Release/update/documentation alignment

**Files:**
- Modify: `.github/workflows/ci.yml`
- Modify: `.github/workflows/release.yml`
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `VERSION`
- Modify: `STABILIZATION_SERIES`

- [ ] **Step 1: Set version line**

`VERSION` -> `2.1.0`; `STABILIZATION_SERIES` -> `2.1` if workflow logic uses the series.

- [ ] **Step 2: Update release workflow**

Unlock release check to `2.1.*`, publish `pkg_core_${VERSION}.zip`, validate `updates/pkg_core.xml`, and use release title `Core ${VERSION}`.

- [ ] **Step 3: Update docs/changelog**

Document canonical package as `pkg_core`, child identifiers unchanged, and automatic Joomla update detection/manual installation.

- [ ] **Step 4: Run full validation**

```bash
bash build/build.sh
php build/update-feed-checksum.php
```

Expected: deterministic build and all local contracts PASS.

- [ ] **Step 5: Open PR and require green CI/runtime before merge**

Do not publish until migration runtime job proves the legacy-to-canonical path.
