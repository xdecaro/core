# xdecaro integration contracts

## Purpose

xdecaro products must be able to refer to entities owned by other products without reading or writing each other's internal database tables. Core therefore defines a very small, domain-neutral reference contract.

Canonical PHP contracts from Core 1.3.0 are:

- `xdecaro\Core\Integration\EntityReference`;
- `xdecaro\Core\Integration\RelationReference`;
- `xdecaro\Core\Integration\Capability`;
- `xdecaro\Core\Integration\IntegrationEvent`.

The former `Xdecaro\Core` prefix is compatibility-only for already-published consumers and must not be introduced by new code.

## EntityReference

An entity reference has exactly three required fields:

- `component` — Joomla component element, for example `com_decaromembership`;
- `entity` — stable entity type owned by that component, for example `member`;
- `id` — stable entity identifier represented as a string internally.

Example payload:

```json
{
  "component": "com_decaromembership",
  "entity": "member",
  "id": "125"
}
```

The component owns the meaning, authorization and lifecycle of the referenced entity. A consumer must not infer table names, PHP classes or storage paths from this reference.

## RelationReference

A relation reference connects a source entity to a target entity with a stable relation type. Relation types are short lower-case identifiers such as `participant`, `attachment`, `certificate`, `source_submission` or `related_event`. A relation type does not transfer ownership.

## Stable Joomla component identifiers

Cross-product references use the installed Joomla component element, not repository names or visible product names. Current identifiers include:

- Forms: `com_decaroforms`;
- Courses: `com_decarocourses`;
- Competitions: `com_xdecarocompetitions`;
- Documents: `com_decarodocuments`;
- Membership: `com_decaromembership`;
- Events: `com_decaroevents`;
- Editor: `com_decaroeditor`;
- Finance: `com_decarofinance`;
- Protocol: `com_decaroprotocol`;
- Draw: `com_xdecarodraw`;
- Organizations: `com_xdecaroorganizations`;
- People: `com_xdecaropeople`;
- Inventory: `com_xdecaroinventory`;
- Resources: `com_xdecaroresources`;
- Notifications: `com_xdecaronotifications`;
- Tasks: `com_xdecarotasks`;
- Analytics: `com_xdecaroanalytics`.

Do not invent identifiers for Communication, Bookings or another future product before its manifest establishes them.

## Cross-product examples

Membership to Courses:

`com_decaromembership/member/125 -> com_decarocourses/enrollment/487` with relation type `participant`.

Membership to Competitions:

`com_decaromembership/member/125 -> com_xdecarocompetitions/participant/88` with relation type `participant`.

Forms to Membership:

`com_decaromembership/application/42 -> com_decaroforms/submission/843` with relation type `source_submission`.

Documents can associate a managed document with any entity through a public reference without knowing the consumer's private tables, for example:

`com_decarodocuments/document/900 -> com_xdecarocompetitions/participant/88` with relation type `attachment`.

## Persistence policy

Core does not define a central relation table. Different products own relation metadata, lifecycle and ACL unless multiple real consumers later prove the same domain-neutral persistence requirement.

## Integration rules

Consumers should:

1. exchange `EntityReference` values or equivalent serialized payloads;
2. use stable public APIs/services/events of the owning product;
3. check optional product availability before invoking it;
4. fail gracefully when an optional integration is unavailable;
5. preserve ACL at the owning product boundary;
6. never assume that knowing a reference grants permission.

Consumers must not:

- query another product's private tables as an integration API;
- instantiate another product's internal classes by filesystem path;
- derive filesystem paths from entity IDs;
- create circular mandatory dependencies;
- move product-domain logic into Core.

## Compatibility

Published entity/relation identifiers are public API. Prefer additive evolution and deprecation before removal. The Core 1.3 namespace case transition follows the same rule: canonical lowercase namespace first, temporary compatibility for the previous prefix, removal only in a future major after verified migration.
