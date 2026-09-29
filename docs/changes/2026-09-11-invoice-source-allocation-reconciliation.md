# Invoice source allocation reconciliation — 2026-09-11

Baseline: `7ceb7966d3cf81a43a08ba8177a0327407b897ea`, independently verified on `worktree-0.0.8` before this change.

The Rental financial-integration review found that Invoice's default source allocation divided quantity first and truncated each allocation independently. Regression tests reproduced an incorrect `0.999999` allocation for `3 × 1 / 3`, a missing final residual, failure to catch up earlier rounding, and acceptance of a negative cumulative adjustment after a nonproportional allocation.

Fix this in the owning Invoice service. Read the surviving allocated amount alongside quantity in the existing scoped query. Compute the cumulative proportional amount with an exact intermediate product, then subtract the surviving monetary allocation. Source-owner explicit amounts retain their existing behavior. Reject a negative automatic result with a reviewable error; never clamp, rewrite history or assume a credit. Use named zero/product-scale constants. The source owner must still serialize commands on its aggregate and preserve source valuation; no new lock-order or real-engine guarantee is asserted.

Eight new database-backed tests cover exact division, sequential residual reconciliation, released quantity after reversal, unchanged historical values, untrusted caller previous quantity, explicit amounts, nonproportional rejection, tenant isolation and a fractional quantity with the smallest stored amount. Reversal history is a fixture for allocation arithmetic, not a replacement for Invoice reversal workflow tests.

Relationship review: the existing source line to invoice relationship is necessary to identify surviving allocations. Retain it. No schema, ownership or inverse relationships change. No Rental business rate, old Rental code, tax setting or account assignment is introduced.

Update knowledge base section 37 and the completed-work ledger in the TODO. Actual commercial workflows are not marked completed by this shared financial correction.

Verification:

- Full PHP/SQLite suite: **754 tests, 8,261 assertions**.
- Full frontend suite: **83 files, 307 tests**. An initial redirected run did not yield a complete successful report; the final verbose run completed successfully with exit code zero.
- Pint, ESLint, TypeScript, Vite production build and whitespace checks passed.
- Fresh migrations and seeders passed on a new isolated SQLite database.
- Final scope/diff reviewed; no schema changes or historical transaction updates.

Real MySQL contention, installed-production upgrades and browser/UAT were not exercised by these checks. No paid tools, CI run or deployment.
