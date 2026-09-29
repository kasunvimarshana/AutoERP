# Rental partial odometer integrity — 2026-09-09

Baseline verified locally and on GitHub: `5fcf9c87f39f7ec6f05b059d82bc12e5a30b2f1d`, `worktree-0.0.8`.

## Reproduced problem

The fresh Running Chart finalizer compared only start-to-prior-end and end-to-following-start. Missing measurements bypassed custody or adjacent-chart checks. Return validation also considered only finalized end readings. Four new regressions failed on the baseline: a known chart end below handover with unknown start; decreasing readings between partial adjacent charts; a backfilled partial chart exceeding a later known end; and a return below a finalized start with unknown end.

## Correction and business boundary

Use the earliest/latest known chart observations for continuity comparisons. Adjacent queries include charts with either endpoint known. Return checks both recorded endpoints. Nulls remain null; total distance remains unknown unless both readings exist. This implements the existing integrity-derived monotonicity rule and knowledge-base unknown/zero distinction; it does not invent a missing reading, charge, odometer-reset policy or financial entitlement.

The fixes stay in Rental because Rental owns these custody/chart observations. No schema, relationship, module dependency, external Vehicle odometer master or financial module is changed. Existing vehicle-first locks and current locking reads remain in place. Removed Rental code was not inspected or reused.

## Verification

- Four new regressions failed before the fix and passed afterward.
- A positive partial-reading regression verifies preserved null measurement, unknown total KM and immutable history.
- Complete PHP/SQLite suite: **697 tests, 7,887 assertions passed**.
- PHP Pint and git whitespace checks passed.
- No frontend files changed; frontend checks were not repeated for this backend-only correction.
- Free local tools only; no GitHub Actions or deployment.

The broader module is still incomplete. Financial policies and owner-module handoffs, driver identity, full audiovisual evidence review, production schema reconciliation, MySQL contention and browser/UAT remain outstanding. This verification does not resolve those requirements.
