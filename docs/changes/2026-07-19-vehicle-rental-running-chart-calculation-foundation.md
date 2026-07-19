# Vehicle Rental Running Chart and Calculation Foundation

Date: 2026-07-19

## Scope

This change extends the fresh Vehicle Rental operational foundation with:

- one physical Daily Running Chart for each active customer-use assignment and operational date;
- Draft, Finalized, and Reversed chart states;
- append-only correction lineage through `replaces_running_chart_id`;
- exact odometer, garage-kilometre, commercial-kilometre, overtime, night-out, time, AC-mode, trip, and remarks snapshots;
- vehicle and driver time-overlap prevention;
- odometer continuity checks with an explicit variance reason for legitimate gaps;
- chart-aware assignment Return and Replace controls;
- immutable customer-side and owner-side calculation snapshots;
- line-level rate/version snapshots;
- portable active-source markers preventing duplicate same-side chart consumption;
- calculation cancellation that releases sources without deleting history.

## Central invariant

One finalized physical Running Chart may be consumed independently by:

1. the customer agreement calculation; and
2. the owner agreement calculation, when the customer assignment references an owner-supply source assignment.

A calculation on one side does not block the other side. The same Running Chart cannot be consumed twice by active calculations on the same side.

## Confirmed calculation policy

The calculation engine intentionally supports only rules that are deterministic in this release:

- A calculation accepts at most one finalized chart per operational date until replacement-day and concurrent-vehicle charging rules are confirmed.
- Daily basis uses one finalized chart per operational date.
- Daily base quantity is the number of operating dates.
- Daily included kilometres are `agreement included_km × operating dates`.
- Daily AC-specific agreements use the actual Running Chart AC mode and the matching effective rate.
- Monthly basis requires one complete calendar month; partial-month proration is rejected.
- Monthly included kilometres are applied once for the month.
- Commercial kilometres are `end odometer - start odometer - garage kilometres`.
- Excess kilometres are `max(0, commercial kilometres - included kilometres)`.
- Driver salary is calculated only for charts carrying an assigned-driver snapshot.
- Self-drive charts cannot contain driver overtime or night-out facts.
- Normal/double/triple overtime and night-out quantities come only from finalized driver-operational facts and effective agreement rates.
- Every calculation period must be covered by exactly one rate version. Requests crossing a rate revision boundary must be split.

## Fail-closed rules

The videos do not establish exact rules for replacement-day double charts, partial-month proration, the legacy excess-kilometre modes, fixed “Other” rates, tax rounding, or owner withholding. This release rejects those ambiguous calculation situations instead of guessing.

## Ownership boundaries

- Vehicle Rental owns Running Charts, calculation snapshots, source consumption, and rental operational lifecycle rules.
- Vehicle continues to own the shared availability contract.
- Vehicle Service continues to contribute workshop availability blockers.
- Invoice, Payment, Tax, Finance, and Reporting remain the owners of their respective downstream records.

## Deliberately excluded

- Customer Invoice creation
- Owner Payable / self-billed settlement creation
- VAT, SSCL, SVAT, withholding, and tax snapshots
- Finance posting and accounting-period checks
- Deposit movements
- Rental reports
- React Running Chart and calculation pages

The calculation resource exposes `subtotal_amount`, not a final tax-inclusive or withholding-adjusted document total.

## Verification required before merge

Run from a complete checkout:

```bash
composer install
php artisan test --filter=VehicleRental
php artisan test
composer test:mysql
php artisan migrate:fresh --seed
npm ci
npm run typecheck
npm run lint
npm run test
npm run build
```
