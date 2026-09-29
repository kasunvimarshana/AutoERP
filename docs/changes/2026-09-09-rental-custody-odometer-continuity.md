# Rental custody odometer continuity — 2026-09-09

Baseline: `baa436fc4c476eedd3ff8c61a48a485b48e2c8df`, verified against `worktree-0.0.8` before editing.

## Reproduced defect and correction

Three regressions failed on the baseline: a later handover below the previous return with no Running Chart; a backfilled return above a later known handover; and a partial chart below a previous use's return when the current handover reading was unknown.

Introduce one Rental-owned `OdometerContinuity` service for timestamped custody and finalized-chart observations. Handover, return and chart finalization use it under the existing physical Vehicle lock. Current locking reads check earlier and later evidence across uses and organizations within the tenant. Simultaneous observations must agree. Unknown values remain null; reversed and draft charts do not establish finalized readings. Replace the narrower adjacent-chart comparison rather than maintain parallel implementations.

This extends the existing integrity-derived monotonicity rule; it does not infer a commercial rule, manufacture an odometer-reset policy, change Vehicle's master odometer, or rewrite historical observations. Validation errors disclose no other-branch details.

Relationship review: no schema or relationship changes. The service traverses the existing chart-to-use-to-vehicle relationship and does not duplicate physical identity. Responsibility remains within Rental. No removed Rental implementation was restored or reused.

## Verification and limitations

- Three new regressions failed before the fix and passed afterward; checks include atomic state/history preservation, six-decimal comparison, accepted equal boundary readings, and preserved unknown quantity.
- Rental suite: **34 tests, 218 assertions passed**.
- Full PHP/SQLite suite: **703 tests, 7,933 assertions passed**.
- PHP Pint and git whitespace checks passed.
- No frontend changes; existing frontend verification was not rerun for this backend correction.
- Free official Ubuntu MariaDB 10.11.14 packages were downloaded with package-metadata SHA-256 verification and extracted into scratch. A disposable data directory initialized. The socket-only server failed with `UNIX Socket: Operation not permitted`; no real-engine application or contention tests ran. No production database or system service was modified. This does not close MySQL acceptance.
- Free tools only; no GitHub Actions or deployment.

Knowledge base, operations contract and TODO reflect the correction and database-test limitation. The complete module is still unfinished: effective successor terms, canonical driver identity, commercial calculations/handoffs, broader reports, complete audiovisual/source review, real-engine contention, production migration and browser/UAT acceptance remain outstanding.
