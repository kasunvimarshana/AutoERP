# Vehicle Rental usage-charge commercial calendar correction

Date: 2026-09-29

Authoritative base: `worktree-0.0.8` at `8de690c4d433e10dd4a948b1023cf93572e80d6b`.

## Root cause

The fresh Vehicle Rental module already defined tenant/organization `localization.timezone` as the commercial civil-day source of truth for agreement coverage, Vehicle Use admission, owner-source coverage, successor activation, closure, base rent and mileage.

Ordinary chart-derived OT/night-out charges were the remaining exception. `UsageChargeBilling` persisted `period_from` / `period_until` from the original submitted timestamp offset. `AgreementService` also tested Vehicle Use successor cutover against midnight in the submitted `ends_at_input` offset.

Those representations preserve useful source evidence, but they are not the Rental commercial calendar. Around a civil-day boundary they can disagree with the configured workspace date and therefore make successor cutover, retained-charge blocking and invoice supply dates inconsistent.

## Correction

- `UsageChargeBilling` now resolves the configured workspace timezone through the existing `RentalCalendar`.
- New OT/night-out charges snapshot `timezone`, `supply_from` and `supply_until` in their immutable calculation.
- `period_from` / `period_until` use those same workspace-calendar dates.
- The interval remains half-open: an `ends_at` exactly at tenant-local midnight belongs to the preceding supplied commercial day (`ends_at - 1 microsecond`).
- Invoice supply-period snapshots automatically consume the frozen `supply_from` / `supply_until` values through the existing `RentalChargeDocuments` owner boundary.
- Successor cutover now converts each Vehicle Use end instant into the same workspace timezone before comparing it with the successor civil-date boundary.
- Original `starts_at_input` / `ends_at_input` values remain unchanged as physical/source-offset evidence.

No new schema, relationship, tariff, tax rule, GL rule or cross-module ledger was introduced.

## Historical-data policy

Existing Rental charges are immutable source evidence and may already be linked to Invoice documents. This correction does **not** rewrite old charge periods or invoice supply periods. New charges use the corrected commercial-calendar snapshot. Existing records remain auditable under the semantics captured when they were created; any historical financial correction must use the governed Invoice/Rental release, void and reissue flow rather than a silent data rewrite.

## Regression coverage added

`UsageChargeTenantCalendarTest` covers:

- source offset one civil day ahead of the workspace calendar;
- source offset one civil day behind the workspace calendar;
- half-open usage ending exactly at workspace midnight;
- persisted Rental charge period;
- immutable calculation timezone/supply-period snapshots;
- Invoice supply-period snapshot.

`AgreementSuccessorCommercialCalendarTest` covers:

- a returned Vehicle Use and retained usage charge that end before the workspace successor boundary even when their source offset shows the successor date;
- a Vehicle Use that crosses the workspace successor boundary even when its source offset still shows the previous date.

## Verification contract

The implementation remains limited to Vehicle Rental-owned services/tests plus this append-only change record. No legacy Rental code is restored or referenced.

Executable repository-wide verification must only be claimed when actually run. Direct Git/GitHub checkout from the execution container remained DNS-blocked during this pass, and GitHub Actions were intentionally not used. Final acceptance therefore includes connector-backed source/diff review and any local syntax checks that can be executed from materialized changed files; unavailable dependency-backed suites are not to be reported as passed.
