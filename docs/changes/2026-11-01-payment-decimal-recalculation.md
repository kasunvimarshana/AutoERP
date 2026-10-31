# Payment exact-decimal recalculation — 2026-11-01

## Root cause

`PaymentCalculationService::recalculate()` used database `SUM()` for payment line totals, active invoice allocations and posted refunds. On SQLite, SUM of NUMERIC/DECIMAL values can produce a binary float (including scientific notation for micro amounts). Passing `(string) $sum` back to the exact `DecimalMath::normalize()` may reject valid persisted payment amounts or silently lose significant fractional units. Payment amounts, unapplied balances and downstream postings must not depend on the database driver's aggregate return type.

## Owning Payment implementation

Each of the three relevant persisted monetary streams is now aggregated with the existing `DecimalMath::sum()` over lazily streamed Eloquent DECIMAL strings, not database SUM: `payment_lines.amount`, active `payment_allocations.allocated_amount` and eligible approved/posted `payment_refunds.amount`. The qualifying refund statuses and all existing negative/unapplied guards remain unchanged. No floating point, synthetic rates, extra tables, policy defaults or Finance-owned calculations were introduced.

## Regression coverage and verification boundary

Added `PaymentCreationIdempotencyTest::test_payment_recalculation_preserves_six_decimal_micro_amounts`, creating three real persisted `0.000001` payment lines under the existing test tenant/method fixture. It requires recalculated total/unapplied `0.000003`, allocated/refunded zero and preserves the existing independent idempotency tests.

**The updated Laravel suite has not been run in this session**: the user's local Windows checkout/Composer/database is unavailable from this container. Commit and source review are actual, but no full backend/MySQL/frontend pass or historical reconciliation is claimed. Run the new micro-amount test and the full application suite on the exact candidate before merging or releasing. No Actions, paid services or production DB writes were used.
