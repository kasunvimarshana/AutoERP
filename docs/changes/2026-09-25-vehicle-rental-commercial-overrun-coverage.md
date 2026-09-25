# Vehicle Rental commercial overrun coverage

Date: 2026-09-25

## Problem

The fresh Rental implementation correctly treated Running Charts as physical evidence and already capped base rent and mileage at agreement coverage. A deeper continuation audit found that typed Running Chart charges such as Normal/Double/Triple OT and Night-out could still reach Invoice/AP when actual custody overran the agreement's contractual `ends_on`.

That was inconsistent with the canonical rule "do not invent money": a real physical overrun is evidence that the vehicle was used, but it is not proof that an expired customer or owner rate automatically extends.

## Root-cause fix

`RentalChargeDocuments` now validates the immutable Rental source supply period immediately before financial-document creation.

- Commercial coverage starts at agreement `starts_on`.
- Commercial coverage ends at the stricter of contractual `ends_on` and immutable lifecycle `closed_on`, when either exists.
- Base-rent calculations use their recorded `from` / `until` period.
- Mileage uses its recorded `supply_from` / `supply_until` period.
- Running Chart usage charges derive the true half-open physical interval from the immutable chart timestamp snapshot, so an exact midnight end belongs to the preceding covered instant rather than inventing an extra service day.
- Fallback to the persisted charge period exists only for source types without a more precise immutable calculation period.

If the source period is not covered, financial handoff fails atomically. The physical Running Chart remains finalized and auditable. Any genuine uncovered financial consequence requires a valid agreement revision or an explicit governed adjustment in the owning financial workflow.

## Ownership and relationship review

No schema relationship changed. No new ledger, side table, compatibility layer, tax rule, tariff or magic value was introduced.

The guard belongs in `RentalChargeDocuments` because that service is the existing Vehicle Rental boundary that converts immutable Rental source charges into Invoice/AP documents. Running Chart remains the owner of physical usage evidence; Invoice/AP remains the owner of financial document lifecycle.

## Regression coverage

`RentalChargeCoverageTest` proves that a finalized physical overrun can be retained while both customer and owner automatic usage billing are rejected and rolled back when the chart extends beyond agreement coverage.

## Verification evidence

- Exact changed PHP files are syntax-checked in the available local PHP runtime before merge.
- Final branch diff is reviewed for unrelated changes and cross-module leakage.
- No GitHub Actions result is used as verification evidence.
- Full dependency-backed PHPUnit/frontend/MySQL execution is reported only if it is actually available in the execution environment; it is never inferred from static review.
