# Courses UI Parity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the strongest reusable administrator-list patterns from Courses 1.5.0 into Core shared UI, then make People consume those primitives without copying Courses-specific CSS.

**Architecture:** Courses remains functionally independent and is used only as a visual/reference implementation. Core owns the shared page hierarchy, KPI cards, filter panel, table surface, status badges, responsive behavior and dark-mode-safe tokens. People keeps only domain-specific layout rules and consumes Core primitives.

**Tech Stack:** Joomla 6.1.3, PHP 8.3+, Joomla Web Asset Manager, CSS container queries, Core shared administrator assets.

**Spec:** User-approved Courses 1.5.0 list view screenshots and repository implementation.

## Global Constraints

- Joomla target remains exactly 6.1.3.
- PHP minimum remains 8.3.0.
- No Courses-specific `dc-*` selectors are copied into Core.
- No component functionality, ACL, import/export, duplicate handling or data behavior is removed.
- Shared CSS stays opt-in under `.xdecaro-suite` / `.xdecaro-*` selectors.
- Light/dark mode and 375/393px mobile layouts must remain supported.
- New distributed test artifacts use Core 2.2.2 and People 1.8.2; do not reuse prior test version numbers.

## Review Focus

- Five KPI cards must remain balanced on desktop and become two columns on normal phones without horizontal overflow.
- Very long labels/titles must wrap without increasing page width.
- Tables must not inherit Bootstrap striped rows when using the shared Core list surface.
- Filter controls must remain keyboard-accessible and stack cleanly on narrow screens.
- Dark mode must keep borders, muted text, status badges and table headers readable.

---

### Task 1: Core shared list-page visual contract

**Files:**
- Modify: `src/plg_system_xdecarocore/media/css/admin.css`
- Modify: `src/plg_system_xdecarocore/media/css/components.css`
- Modify: `tests/core-2.2-admin-ui-contract.php`

**Interfaces:**
- Consumes: existing `.xdecaro-suite__page-header`, `.xdecaro-suite__metrics`, `.xdecaro-suite__metric`, `.xdecaro-filterbar`, `.xdecaro-suite__responsive-table`.
- Produces: compact Courses-inspired page hierarchy and shared list/table surface while preserving existing class names.

- [ ] **Step 1: Write failing contract assertions** for compact header sizing, KPI top alignment, list-panel spacing, non-striped table rows, status badges and mobile two-column metrics.
- [ ] **Step 2: Run Core contract and confirm RED.**
- [ ] **Step 3: Implement shared CSS primitives** without global Joomla selectors or `dc-*` compatibility aliases.
- [ ] **Step 4: Run Core CI/runtime/layout contracts and confirm GREEN.**

### Task 2: Core 2.2.2 package metadata

**Files:**
- Modify: `VERSION`
- Modify: Core library/component/plugin/package manifests and Web Asset registries.
- Modify: `updates/pkg_core.xml`, `updates/changelog.xml`, `CHANGELOG.md` as required by repository release contracts.

**Interfaces:**
- Produces: deterministic installable `pkg_core_2.2.2.zip`.

- [ ] **Step 1: Bump all source/package metadata to 2.2.2.**
- [ ] **Step 2: Run deterministic build and package validation.**

### Task 3: People consumes the finalized shared list UI

**Files:**
- Modify in `xdecaro/people`: `component/admin/tmpl/people/default.php`
- Modify in `xdecaro/people`: `component/media/css/admin.css`
- Modify in `xdecaro/people`: UI contract tests and version metadata.

**Interfaces:**
- Consumes: Core 2.2.2 shared page header, KPI, filterbar, badges, list panel and responsive table.
- Produces: People 1.8.2 with no duplicated Courses/Core visual rules.

- [ ] **Step 1: Add/adjust People contract assertions** requiring shared Core classes and forbidding `table-striped` plus redundant filter sizing overrides.
- [ ] **Step 2: Run contract and confirm RED.**
- [ ] **Step 3: Update People list markup/CSS** preserving all existing actions, ACL, pagination, export selection and duplicate navigation.
- [ ] **Step 4: Bump People to 1.8.2 and require Core 2.2.2.**
- [ ] **Step 5: Run People CI/package/runtime contracts and confirm GREEN.**
