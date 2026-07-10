# Vehicle Rental running-chart odometer continuity

Date: 2026-07-11

## Context

The running-chart service locked the vehicle timeline and rejected odometer rollback, but it allowed an unexplained forward gap between adjacent usage records. A previous finish of `100` followed by a new start of `110` could therefore be stored without an audit reason, leaving ten kilometres outside the operational evidence chain.

## Changes

- Adjacent running-chart odometer values must now match exactly in both chronological directions.
- Any mismatch remains permitted only when `odometer_variance_reason` is explicitly supplied and persisted with the new usage record.
- Error messages now describe a general mismatch rather than only rollback or overrun cases.
- A focused contract test protects the exact-match-or-reason rule.

## Scope and verification

The change is limited to `RentalUsageService`, its regression guard, and this append-only record. No billing, owner-cost, invoice, payment, schema, or cross-module behavior changed. The reconstructed current source matched the authoritative Git blob before modification, PHP syntax passed, and the published branch diff contains only the intended continuity comparisons and messages.
