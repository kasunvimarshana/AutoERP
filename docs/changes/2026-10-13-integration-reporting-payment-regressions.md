# Integrated reporting, payment and migration regressions

## Changes
- Relocated customer outstanding reporting into Reporting to remove a Finance dependency cycle; corrected invoice party discrimination, grouped projection, currency normalization and customer scoping.
- Preserved the historical inventory index migration identity as an upgrade, with the index explicit in the fresh baseline.
- Corrected refund API function shadowing and guarded outstanding report requests against late responses.
- Kept invoice exchange-rate snapshots immutable when rerunning their backfill.
- Reversed historical whole payments through their original journal without requiring a nonexistent independent allocation journal; retained the single-allocation reversal guard.
- Updated fixtures to exercise source-owned Finance reversal and independent allocation journals without weakening production guards.
- Added report, inventory history, historical payment ledger and browser request regression tests.

## Verification
Focused PHP integration regressions passed: 64 tests / 358 assertions; historical inventory/payment regressions passed: 2 tests / 22 assertions. TypeScript and lint passed. SQLite fresh migration, rollback and reapply passed. Complete final candidate verification is recorded separately after the remaining source integration.

## Release status
No production promotion. PR #127 still requires passing hosted checks and independent approval; GitHub Actions reported an account billing lock. Vehicle Rental TODO section R remains open.
