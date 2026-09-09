# Rental authenticated operational acceptance — 2026-09-09

Baseline: `0158725163ae31dd9a5e3e5450aaa9c4bc254ca2`, verified against `worktree-0.0.8` before editing and before publication.

## Scope and evidence

Add six fresh HTTP integration tests using real Auth login and the complete middleware stack. No Rental authorization mock, middleware disabling, injected request attributes or manufactured access token is used. Customer/Supplier/Vehicle/ownership/subscription records are controlled fixtures using the current owner-module schemas and fixture helpers; provisioning those records through their UI is outside this test scope.

The tests cover the source-backed operational flow documented by V01/V03/V04/V05/V07 and the existing integrity-derived workflow:

- Independent customer and owner agreement creation/activation, source lookup, planned use, actual handover, Running Chart finalization/reversal/correction, return, closure and readable history.
- Rejection of premature closure, stale reversal and finalized-fact editing; preserved original chart measurements.
- Company-source replacement: failed-source rollback, preserved predecessor identity, no artificial Owner Agreement, stale-repeat rejection and final return.
- Anonymous access, missing feature/permissions and independent customer/owner/chart read/finalize/reverse authority.
- Foreign-tenant IDs, client-supplied context overrides, branch-bound session isolation and revoked branch membership.

Initial failures were incorrect test expectations for the source-selector response shape and the documented explicit numeric-offset timestamp format. Align the tests with those existing contracts. No production defect was reproduced, so no runtime logic was changed to satisfy the tests.

## Documentation and ownership

Reconcile completed operational TODO items against the fresh implementation and tests. Explicitly distinguish recorded rates/facts from still-unimplemented calculations, taxes, financial handoffs and driver qualification. Keep effective successor terms, canonical driver identity, financial consumption, MySQL contention, source review and release acceptance open. Correct stale agreement documentation that still described vehicle-use integration as future work.

Relationship review: no schema, relationship, module dependency or ownership changes. No old Rental code was inspected, restored or reused. This change adds verification and documentation, not new business policy.

## Verification

- Authenticated suite: **6 tests, 101 assertions passed** on PHP 8.3/SQLite.
- Full PHP/SQLite suite: **709 tests, 8,034 assertions passed**.
- PHP Pint and whitespace checks passed.
- No frontend or runtime changes; frontend checks were not repeated.
- Free local tools only; no GitHub Actions, production database changes or deployment.

These are application HTTP tests, not browser/UAT, MySQL locking or production migration acceptance. Financial calculations, invoices/payables, receipts/payments, taxes and GL are not covered or claimed complete. The full module remains unfinished.
