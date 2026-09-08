# Xdecaro Core — Codex Repository Rules

## Project purpose

**Xdecaro Core** is the shared technical foundation for the Xdecaro Joomla ecosystem.

Repository: `xdecaro/core`

Core must remain small, stable, predictable, reusable and domain-neutral.

It may provide common infrastructure used by multiple Xdecaro extensions, but it must not absorb business logic that belongs to Forms, Courses, Competitions, Documents, Membership, Events, Bookings or future products.

Target Joomla versions are Joomla 4, 5 and 6 where technically possible without fragile compatibility workarounds.

## Core boundary

Good Core responsibilities include:

- shared utility and helper classes that are genuinely generic;
- shared services and internal APIs;
- Joomla compatibility helpers when a real version difference requires them;
- Web Asset Manager registration and shared asset conventions;
- shared design tokens and `.xdecaro-*` UI primitives;
- responsive and light/dark mode foundations;
- common buttons, badges, cards, tables, modals, alerts, empty states and loading states;
- generic JavaScript utilities;
- generic AJAX/CSRF helpers;
- dependency and version checks;
- extension registry and diagnostic infrastructure;
- common events or contracts used by multiple Xdecaro extensions.

Do not put product-specific business logic in Core.

Examples that must remain outside Core include:

- Forms field definitions, form-builder behavior, submission workflows and Forms-specific validation;
- Courses editions, enrollments, lessons, attendance and evaluations;
- Competitions tournaments, phases, groups, matches, standings, rankings, scoring and sports rules;
- Documents document workflows and product-specific retention logic;
- Membership membership workflows;
- Events event-domain workflows;
- Bookings booking-domain workflows.

A feature belongs in Core only when it is truly reusable, independent from a product domain and useful to more than one extension.

## Architecture

Keep clear separation between:

- library;
- plugin;
- package;
- media;
- services;
- APIs;
- tests;
- build tooling;
- documentation.

Avoid circular dependencies.

Products may depend on Core. Core must never depend on an individual product.

Correct direction:

`Forms / Courses / Competitions / Documents / Membership / Events / Bookings -> Xdecaro Core`

Incorrect direction:

`Xdecaro Core -> Forms` or any other individual product.

Core must remain installable even when no other Xdecaro product is installed.

## Public API stability

Treat public Core APIs as stable contracts.

Before changing any public:

- PHP class;
- namespace;
- interface;
- method;
- event;
- service identifier;
- JavaScript API;
- Web Asset identifier;
- CSS class;
- CSS custom property;
- public data structure;

inspect the impact on consuming Xdecaro extensions.

For incompatible changes prefer:

1. introduce the new API;
2. keep the previous API temporarily;
3. mark the previous API as deprecated;
4. remove it only in a future major release.

Do not silently rename or remove public contracts.

## Joomla architecture

Use modern Joomla APIs and conventions:

- namespaces;
- service providers;
- dependency injection where appropriate;
- Web Asset Manager;
- `DatabaseInterface`;
- Form API;
- Language API;
- Registry;
- events;
- ACL;
- Joomla input/filter APIs.

Avoid deprecated APIs when a modern supported equivalent exists.

Do not create compatibility shims unless the actual supported Joomla versions require them.

## Security

Security takes priority over convenience.

Check where relevant:

- server-side ACL;
- Joomla CSRF/token validation;
- input filtering;
- validation;
- output escaping;
- XSS;
- SQL injection;
- bound queries;
- uploads;
- MIME type validation;
- directory traversal;
- unauthorized access.

Never rely only on JavaScript for authorization or validation of security-sensitive operations.

Do not expose stack traces, secrets, credentials, API keys, tokens or sensitive configuration values.

## Database

Use `#__` for Joomla database tables.

Core should have its own persistent tables only when the data is genuinely shared across the ecosystem.

Before adding a table, verify that persistent shared storage is actually required.

Database updates must preserve existing data and configuration.

Do not drop and recreate normal production tables during routine updates when a safe migration is possible.

Check indexes, duplicate queries and queries inside loops.

Use Joomla Database API and bound parameters for untrusted values.

## PHP quality

Prefer:

- clear responsibilities;
- explicit dependencies;
- focused classes and methods;
- useful type declarations where compatible;
- controlled exceptions;
- Joomla logging for diagnostic situations.

Avoid:

- giant generic helper classes;
- hidden side effects;
- duplicated code;
- dead code;
- debugging output;
- `var_dump` / `print_r`;
- unnecessary suppression with `@`;
- speculative abstractions with no real consumer.

Do not refactor large working areas merely for style.

## JavaScript

Keep shared JavaScript small and generic.

Avoid:

- unnecessary globals;
- duplicate listeners;
- duplicate AJAX requests;
- lifecycle bugs in Joomla administrator views;
- avoidable Console errors;
- large framework-like abstractions when native browser/Joomla APIs are enough.

Shared helpers may cover concerns such as loading state, confirmations, notifications, debounce and Joomla-compliant request handling when they remain product-neutral.

## CSS and design system

Shared UI must use scoped Xdecaro conventions.

Prefer:

- `.xdecaro-*` classes;
- `--xdecaro-*` CSS custom properties;
- scoped component primitives;
- responsive behavior;
- Joomla-compatible light and dark mode.

Avoid broad global selectors that could alter unrelated Joomla administrator UI.

Core must not impose a rigid product-specific visual design.

Do not use excessive `!important` rules.

Shared UI must work on desktop, tablet and smartphone.

## Web Asset Manager

Register shared CSS and JavaScript through Joomla Web Asset Manager.

Use stable asset identifiers.

Avoid loading the same Core asset more than once.

Do not load large shared assets globally when they are only required on specific screens.

## Performance

Core can be loaded by many extensions, so keep runtime overhead low.

Avoid:

- unnecessary work on every request;
- repeated filesystem scans;
- repeated extension discovery when results are stable;
- unnecessary database queries;
- queries inside loops;
- globally loaded assets that are not needed;
- duplicate observers/listeners;
- unnecessary AJAX calls.

Cache stable information where safe, but never cache authorization-sensitive state incorrectly.

## Language and accessibility

Use Joomla language files for administrator-facing text.

Do not move product-specific translations into Core.

Shared UI must provide a good accessibility baseline:

- keyboard navigation;
- visible focus;
- meaningful labels;
- accessible names;
- ARIA only where needed;
- sufficient contrast;
- appropriate modal focus management.

Do not communicate important state by color alone.

## Versioning and releases

Use Semantic Versioning:

- PATCH = compatible fixes;
- MINOR = backward-compatible functionality;
- MAJOR = incompatible changes.

Keep version numbers coherent across:

- manifests;
- package metadata;
- changelog;
- update server files;
- SQL update files;
- tags;
- GitHub Releases;
- generated ZIP filenames.

Never distribute different code with the same version number.

Generated ZIPs must be directly installable in Joomla and updates must preserve data and configuration.

## Package structure

Core is expected to ship as package `pkg_xdecarocore`, containing at least:

- library `lib_xdecarocore`;
- system plugin `plg_system_xdecarocore`.

Build extension ZIPs first and place them beside the package manifest without an extra parent directory inside the final package ZIP.

Do not create empty directories only to match a planned structure.

Keep generated release artifacts separate from source.

## Integration with other Xdecaro repositories

When a reusable implementation already exists in Forms, Courses or Competitions and is being considered for Core:

1. inspect the real implementation first;
2. identify the generic boundary;
3. remove product-specific assumptions;
4. design a stable Core API;
5. keep the product implementation working during transition;
6. migrate incrementally;
7. verify real consumers;
8. remove duplicated local code only after the Core replacement is proven.

Do not copy a product-specific implementation into Core unchanged.

Do not force a migration merely for consistency.

## Before modifying

Before changing Core:

1. inspect repository structure and current implementation;
2. inspect manifests, namespaces, services and dependencies;
3. identify public contracts;
4. check which Xdecaro extensions may consume the affected functionality;
5. preserve working behavior;
6. avoid unnecessary refactors;
7. choose the smallest robust and maintainable change.

The repository is authoritative for current implementation. Documentation describes intent but must not blindly override working code.

## Regression policy

Never remove an existing function without explicit request or a verified migration path.

If a public API needs replacement, introduce the replacement first and deprecate the old API before eventual removal in a major version.

Avoid unrelated rewrites during bug fixes or feature work.

## Final tests

Before considering a relevant change complete, verify as applicable:

- clean installation;
- update from the previous released version;
- Joomla 4/5/6 where supported by the implementation;
- backend/frontend areas affected;
- changed services/APIs;
- ACL;
- CSRF;
- database migrations and queries;
- JavaScript Console;
- PHP errors/warnings/deprecations caused by Core;
- desktop/tablet/smartphone;
- light/dark mode;
- accessibility;
- real Xdecaro components consuming the changed functionality;
- final ZIP contents and installability.

Do not claim a test was executed when it was not.

Clearly distinguish between live-tested, statically reviewed and not tested.

## Working rule

When the user says **“procedi”**, execute the requested work directly after inspecting the relevant code and dependencies.

Do not ask for another confirmation when requirements are already clear.

If a proposed technical approach is weaker than a safer or more maintainable alternative, explain the issue and use or recommend the stronger approach.

Core must remain small, stable, predictable and genuinely shared. Every Core feature should reduce real duplication without turning Core into a container for product-specific logic.
