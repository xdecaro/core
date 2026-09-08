# xdecaro Core consumer integration standard

This document is the baseline for every Joomla component in the xdecaro ecosystem.

## Rule

Every current and future xdecaro component must define an explicit integration boundary with **Core by xdecaro**. Integration does not mean moving product logic into Core.

Current consumers include:

- Forms (`com_decaroforms`)
- Courses (`com_decarocourses`)
- Competitions (`com_xdecarocompetitions`)
- Documents (`com_decarodocuments`)
- Membership (`com_decaromembership`)
- Events (`com_decaroevents`)
- Editor (`com_decaroeditor`)
- Finance (`com_decarofinance`)
- Protocol (`com_decaroprotocol`)
- Draw (`com_xdecarodraw`)
- Organizations (`com_xdecaroorganizations`)
- People (`com_xdecaropeople`)
- Inventory (`com_xdecaroinventory`)
- Resources (`com_xdecaroresources`)
- Notifications (`com_xdecaronotifications`)
- Tasks (`com_xdecarotasks`)
- Analytics (`com_xdecaroanalytics`)

Communication, Bookings and every future xdecaro component must follow this standard from their first implementation.

## Technical naming for new products

New/unreleased xdecaro products use the lowercase vendor convention from the first commit:

- Joomla component: `com_xdecaro<product>`;
- package: `pkg_xdecaro<product>`;
- database tables: `#__xdecaro<product>_*`;
- PHP vendor namespace: `xdecaro\Component\<Product>`.

Already-stable products are not renamed merely for cosmetic consistency. A component/package/table rename is a breaking migration and must be handled deliberately with SemVer and an upgrade strategy.

## Dependency direction

Allowed: `Product -> Core`

Forbidden: `Core -> Product`

Core must remain installable and usable without any product component.

## Public references

Canonical Core 1.3+ contracts:

- `xdecaro\Core\Integration\EntityReference`
- `xdecaro\Core\Integration\RelationReference`
- `xdecaro\Core\Integration\Capability`
- `xdecaro\Core\Integration\IntegrationEvent`

The former `Xdecaro\Core` prefix remains temporarily loadable for already-published consumers through Core's legacy compatibility library. New code must not introduce new `Xdecaro\Core` usages.

A reference identifies an entity; it does not grant authorization. The owning component remains responsible for ACL and validation. Components must not read or write another product's private database tables as an integration mechanism.

## Shared UI

When Core shared UI is used, consumers should load it through:

- `xdecaro\Core\Asset\AssetService`
- `.xdecaro-scope`
- `xdecaro.core` / `xdecaro.components`
- `--xdecaro-*` design tokens and `.xdecaro-*` primitives

Core assets are opt-in and must not be injected globally.

Product-specific layout and interaction remain in the product. Do not move Editor canvas behavior, Draw animations, Finance accounting UI, Protocol register logic, Inventory movement logic, Resources allocation metadata, Organizations hierarchy logic, People master-data rules or other product-specific UI/domain logic into Core.

## Fallback and hard dependencies

The dependency policy must be explicit per product:

- if Core is optional, missing or incompatible Core must result in a controlled fallback, never a class-not-found fatal error;
- if Core is a declared hard dependency, the package/installer must validate the minimum supported version clearly before product functionality is used.

Do not silently change an optional Core dependency into a hard dependency in a patch release.

## Diagnostics

Every component should expose enough administrator diagnostics to determine, where applicable:

- installed Core version;
- public reference API availability;
- shared UI/AssetService availability;
- component/package version consistency;
- important integration availability without exposing secrets or private data.

Diagnostics must not make optional integrations appear as critical failures merely because another product is not installed. People diagnostics must never expose person/contact data.

## Product boundaries

Core never owns product-domain state. Examples:

- Forms owns forms and submissions;
- Courses owns courses, editions, lessons, attendance and evaluations;
- Competitions owns competitions, teams, matches and sports rules;
- Documents owns document storage/workflows;
- Membership owns membership records/workflows;
- Events owns event registrations and event lifecycle;
- Editor owns editing engine, blocks, media behavior and history;
- Finance owns budgets, obligations, payments, deposits and financial ledgers;
- Protocol owns registers, numbering and protocol records;
- Draw owns draw sessions, constraints, execution, results and live presentation;
- Organizations owns organization/legal-entity master records and hierarchy;
- People owns reusable person master records, separate from Joomla authentication and Membership lifecycle;
- Inventory owns physical item catalog, stock quantities and inventory movements;
- Resources owns reusable allocatable resource definitions and capacity/availability-oriented metadata;
- Notifications owns notification delivery/read-state workflows;
- Tasks owns task, assignment, status and due-date workflows;
- Analytics owns analytics/reporting-domain aggregation and presentation;
- Communication owns communication/message workflows when implemented;
- Bookings owns booking-domain workflows when implemented.

Important separations:

- People is not Membership and is not Joomla User authentication.
- Inventory is not Resources: Inventory tracks stock and physical movements; Resources describes allocatable resources.
- Resources is not Bookings: Resources owns definitions/capacity metadata; Bookings owns reservations, calendars and reservation state.
- Organizations may reference People, Membership, Finance or other products only through public contracts; it does not absorb their workflows.
- Analytics may consume public events/contracts but must not query private product tables as a shortcut.
- Notifications and Tasks remain products; Core only supplies domain-neutral envelopes/contracts.

A feature belongs in Core only after it is demonstrably domain-neutral and reusable by more than one product.

## Minimum implementation checklist

Before considering a component Core-integrated, verify:

1. stable Joomla component/package identifiers;
2. no circular dependency;
3. canonical `xdecaro\Core` public API usage for new code;
4. no direct access to another product's private tables;
5. ACL and CSRF enforced by the owning component;
6. shared UI loaded through Web Asset Manager/AssetService when used;
7. `.xdecaro-scope` around shared primitives;
8. local product styles limited to product-specific layout/behavior and safe fallback tokens;
9. controlled behavior when an optional Core is absent/incompatible, or explicit installer enforcement for a hard Core dependency;
10. automated smoke coverage for the Core boundary;
11. install/update ZIP and version metadata remain coherent;
12. Joomla 4/5/6 compatibility is claimed only where actually supported/tested.

This contract is documentation, not a reason to expand Core. New shared APIs require evidence from multiple real consumers before being added to Core.
