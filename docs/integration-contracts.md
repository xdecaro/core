# Xdecaro integration contracts

## Purpose

Xdecaro products must be able to refer to entities owned by other products without reading or writing each other's internal database tables.

Core therefore defines a very small, domain-neutral reference contract.

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

The component owns the meaning and lifecycle of the referenced entity.

A consumer must not infer table names, PHP classes or storage paths from this reference.

## RelationReference

A relation reference connects a source entity to a target entity with a stable relation type.

Example payload:

```json
{
  "source": {
    "component": "com_decaromembership",
    "entity": "member",
    "id": "125"
  },
  "target": {
    "component": "com_decarocourses",
    "entity": "enrollment",
    "id": "487"
  },
  "type": "participant"
}
```

Relation types must be short, stable, lower-case identifiers such as:

- `participant`;
- `attachment`;
- `certificate`;
- `source_submission`;
- `related_event`.

A relation type does not transfer ownership of either entity.

## Ownership examples

- Forms owns forms and submissions.
- Courses owns courses, editions, enrollments, lessons, attendance and evaluations.
- Competitions owns competitions, participants, matches, results and standings.
- Membership owns members, renewals, fees, cards and membership workflows.
- Events owns events, sessions, registrations, capacity and check-in.
- Documents owns documents, versions, document access and document lifecycle.
- Editor owns editor content/block behavior and its public editor integration API.

Core owns only the generic reference contract and other genuinely shared infrastructure.

## Stable Joomla component identifiers

Cross-product references must use the installed Joomla component element, not a repository name or visible product name.

Known current identifiers include:

- Forms: `com_decaroforms`;
- Courses: `com_decarocourses`;
- Competitions: `com_decarodcl` (historical/internal identifier retained for upgrade compatibility);
- Membership: `com_decaromembership`.

For Documents, Events and future products, use the actual component element declared by their manifest once implemented. Do not invent an identifier from the repository name.

## Cross-product examples

### Membership to Courses

A member can be linked to a course enrollment:

`com_decaromembership/member/125 -> com_decarocourses/enrollment/487` with relation type `participant`.

### Membership to Competitions

A member can be linked to a competition participant:

`com_decaromembership/member/125 -> com_decarodcl/participant/88` with relation type `participant`.

### Forms to Membership

A membership application can retain the source Forms submission:

`com_decaromembership/application/42 -> com_decaroforms/submission/843` with relation type `source_submission`.

### Documents to any product

Documents can associate a managed document with an entity without knowing the consumer's tables. Until the Documents component manifest defines its stable Joomla element, examples must not invent one.

A target reference can already safely point to an existing product, for example:

`<documents-component>/document/900 -> com_decarodcl/participant/88` with relation type `attachment`.

### Events to Courses or Competitions

An event can be related to an edition, exam, match or competition through stable entity references rather than direct table coupling. Use the actual Events component element once its manifest defines it.

## Persistence policy

Core 1.0.0 does not define a central relation table.

Reasons:

1. the reference contract can be useful without shared persistence;
2. different products may require different relation metadata, lifecycle and ACL;
3. a central table would create uninstall, cleanup and ownership questions that should be solved from real use cases;
4. keeping persistence out of the first public API makes Core smaller and safer.

If multiple products later prove the same persistence requirements, a shared relation service can be added in a backward-compatible Core minor release.

## Integration rules

Consumers should:

1. exchange `EntityReference` values or equivalent serialized payloads;
2. use stable public APIs/services/events of the owning product;
3. check that an optional product is installed before invoking its API;
4. fail gracefully when an optional integration is unavailable;
5. preserve ACL at the owning product boundary;
6. never assume that knowing an entity reference grants permission to read or modify the entity.

Consumers should not:

- query another product's private tables as an integration API;
- instantiate another product's internal classes by path;
- derive filesystem paths from entity IDs;
- create circular mandatory dependencies;
- move product-domain logic into Core.

## Compatibility

Once a component publishes an entity type as part of an integration contract, renaming it is an API change.

Prefer adding aliases/deprecation support before removing or renaming public entity types or relation types.
