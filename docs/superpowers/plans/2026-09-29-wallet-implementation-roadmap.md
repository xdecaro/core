# Xdecaro Wallet Implementation Roadmap

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Execute the approved Wallet architecture in four independently testable releases without reusing version numbers or coupling Membership to provider failures.

**Architecture:** Phase A establishes the provider-neutral Wallet/QR foundation and Membership adapter. Phase B adds Apple as an isolated provider, Phase C adds Google as an isolated provider, and Phase D performs cross-provider stabilization and publishes 1.0.0. NFC/contactless is not part of this roadmap.

**Tech Stack:** Joomla/PHP/MySQL, Xdecaro Core shared UI, Apple Wallet Passes, Google Wallet Generic Passes.

**Spec:** `docs/superpowers/specs/2026-09-29-wallet-architecture-design.md`

## Global Constraints

- Execute phases in order: A -> B -> C -> D.
- Each phase must pass its completion gate before the next begins.
- Version sequence is fixed: `0.1.0` -> `0.2.0` -> `0.3.0` -> `1.0.0`.
- Never publish different code with the same version.
- Membership remains authoritative for cards/validity through every phase.
- Wallet remains optional for Membership through every phase.
- NFC/Apple contactless/Google Smart Tap are deferred to a separate future design.

## Review Focus

- No phase may silently move card ownership or validity into Wallet.
- No phase may introduce a hard runtime dependency from Membership to Wallet.
- No phase may commit provider credentials or raw QR tokens.
- Provider-specific failures must remain isolated from source-domain transactions.
- Release/version metadata must match the phase version exactly.

---

### Task 1: Execute Phase A — Wallet `0.1.0`

**Plan:** `docs/superpowers/plans/2026-09-29-wallet-phase-a-foundation.md`

- [ ] Complete all Phase A tasks and gate.
- [ ] Produce/test exact `0.1.0` package; do not call it `1.0.0`.
- [ ] Confirm Membership works with Wallet installed, disabled and absent.

### Task 2: Execute Phase B — Wallet `0.2.0`

**Plan:** `docs/superpowers/plans/2026-09-29-wallet-phase-b-apple.md`

- [ ] Start from the exact tested `0.1.0` source.
- [ ] Implement Apple provider only; bump every Wallet version surface to `0.2.0` once code changes begin.
- [ ] Pass the Apple completion gate before Google work.

### Task 3: Execute Phase C — Wallet `0.3.0`

**Plan:** `docs/superpowers/plans/2026-09-29-wallet-phase-c-google.md`

- [ ] Start from the exact tested `0.2.0` source.
- [ ] Implement Google provider only; bump every Wallet version surface to `0.3.0` once code changes begin.
- [ ] Pass the Google completion gate before stabilization.

### Task 4: Execute Phase D — Wallet `1.0.0`

**Plan:** `docs/superpowers/plans/2026-09-29-wallet-phase-d-stabilization.md`

- [ ] Start from the exact tested `0.3.0` source.
- [ ] Introduce no new provider/contactless scope; fix only acceptance, security, UX, compatibility and release issues.
- [ ] Bump all final version surfaces to `1.0.0` only for the exact release candidate that passes the full gate.
- [ ] Tag/publish the exact tested ZIP once; any later code change requires a new SemVer version.

## Completion Gate

The roadmap is complete only when Wallet 1.0.0 satisfies the approved architecture acceptance criteria and Membership still owns the source card/validity. NFC/contactless requires a new design and implementation plan after 1.0.0.
