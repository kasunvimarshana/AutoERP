# Add an as-of date filter to inventory availability

## Request

Let users view today's stock or stock at the end of an earlier date, with the existing Availability filters and CSV export.

## Changes

- Added an as-of date picker defaulting to the user's local date. Today uses live balances; earlier dates use the inventory stock-state audit trail through the selected day's local end.
- Added historical balance projection for on-hand, available, reserved, and allocated quantities, plus historical stock-level filtering and summary counts.
- Applied the selected date and timezone to CSV exports and reject future dates.
- Added a tenant, organization-unit, and occurrence-time index to support historical reads.

## Verification

- PHP syntax checks passed for the historical balance service and stock controller.
- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
