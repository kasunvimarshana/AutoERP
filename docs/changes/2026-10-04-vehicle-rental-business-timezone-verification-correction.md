# Vehicle Rental business-time verification correction — 2026-10-04

## Scope

Follow-up verification of the 2026-10-04 Vehicle Rental business-timezone UI finalization on the live authoritative `worktree-0.0.8`.

Starting authoritative head:

- `worktree-0.0.8`: `cb9521d185ef67092ecddd63c33a796ab098a232`
- tree: `e3884aea2db61f1486cd87a247e0df26ebd0a5fa`

Production `worktree` was file-tree identical at the start of this correction.

No legacy Rental code was restored or reused.

## Findings

### 1. Focused test compile blocker

`RunningChartsPanel.test.tsx` contained the same named import twice:

- `configureBusinessTimeZone`
- `formatBusinessDateTime`

It also repeated the same business-time display assertion twice.

A duplicate named import is a TypeScript binder error (`TS2300 Duplicate identifier`). Therefore the post-timezone frontend tree could not honestly be described as typecheck-clean even though the production Rental runtime code itself was not affected.

Fix:

- removed the duplicate import;
- removed the duplicated assertion;
- retained the single intended business-time display assertion.

A fresh scan of all timezone-delta focused tests found no remaining duplicate import, duplicate imported identifier, or consecutive duplicate-line artifact.

### 2. Ambiguous DST wall time was silently resolved

The shared `businessTimestampWithOffset` correctly rejected nonexistent spring-forward wall times, but an ambiguous fall-back wall time could map to two valid instants and the resolver silently selected one.

Concrete evidence:

`America/New_York 2026-11-01T01:30` maps to both:

- `2026-11-01T05:30:00Z` / `-04:00`;
- `2026-11-01T06:30:00Z` / `-05:00`.

Choosing either without explicit user disambiguation is guessing and violates the AutoERP integrity rule.

## Root fix

The fix belongs to the shared business-time owner, not to a Rental compatibility layer.

`businessTimestampWithOffset` now verifies that exactly one instant represents the supplied wall-clock value.

Implementation details:

- named constants define the day/hour/minute probe units;
- the resolved timezone offset plus offsets one day before and after the resolved instant are tested;
- candidate instants are deduplicated by epoch timestamp;
- a configured wall time is accepted only when exactly one candidate maps back to the requested local date/time;
- nonexistent wall times remain rejected;
- ambiguous fall-back wall times are now rejected;
- when no configured business timezone exists, the browser-local fallback also checks ambiguity when the browser exposes a valid IANA timezone.

No arbitrary Rental rate, timezone, offset or financial value was added.

## Focused coverage

Added/updated tests now cover:

- shared rejection of `America/New_York 2026-11-01T01:30`;
- Vehicle Rental propagation of the same ambiguity rejection through `timestampWithOffset`;
- the existing Colombo conversion;
- New York standard and daylight-saving offsets;
- the existing spring-forward nonexistent-time rejection;
- seconds-preserving configured-zone round-trip.

## Independent local algorithm verification

The corrected resolution algorithm was executed with local Node.js against these cases:

- `Asia/Colombo 2026-09-07T09:00` -> `+05:30`;
- `America/New_York 2026-01-15T09:00` -> `-05:00`;
- `America/New_York 2026-07-15T09:00` -> `-04:00`;
- `America/New_York 2026-03-08T02:30` -> rejected;
- `America/New_York 2026-11-01T01:30` -> rejected.

The container's local TypeScript compiler was also used to confirm that duplicate named imports are reported as `TS2300 Duplicate identifier`, validating the compile-blocker finding.

## Static gate

After the correction:

- focused timezone-delta tests: no duplicate imports/imported identifiers or consecutive duplicate-line artifacts;
- changed Rental production components: no unused-import candidates;
- changed Rental production components: no `TODO`, `FIXME`, `HACK`, or `XXX`;
- no Rental-owned `Intl.DateTimeFormat().resolvedOptions().timeZone` call remains;
- no backend, schema, migration, API, tax, posting or financial-calculation change is part of this correction.

## Full-suite boundary

The latest complete dependency-backed application baseline remains:

- Laravel: **881 tests / 9,697 assertions passed**;
- TypeScript typecheck passed;
- ESLint passed cleanly;
- Vite production build passed;
- Vitest: **101 / 101 files, 374 / 374 tests passed**.

That baseline predates the business-timezone UI delta.

The current execution environment still has no complete AutoERP checkout/dependency tree and cannot obtain one through GitHub/npm DNS, while hosted GitHub Actions remain excluded by the free-tools-only instruction. Therefore a complete post-correction dependency-backed application run is not claimed.

This correction removes the proven TypeScript compile blocker and adds focused source/algorithm verification; it does not fabricate a full-suite result.

## Architecture / business result

No Vehicle Rental backend relationship, business rule or financial behavior changed.

The configured business timezone remains owned by AutoERP configuration/Auth bootstrap and consumed by the shared business-time utility. Rental delegates operational timestamp conversion and display to that source.

Ambiguous local wall times now follow the project-wide integrity rule:

> If one local wall-clock value maps to more than one instant, do not guess. Reject it and require an unambiguous time.

## Decision

The correction resolves the two newly proven defects in the 2026-10-04 timezone delta:

1. focused frontend test compile artifact;
2. silent DST fall-back ambiguity selection.

No other evidence-backed Vehicle Rental implementation gap was found in this continuation.
