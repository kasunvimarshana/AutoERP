# Vehicle Rental Integrity Foundation

## Scope

This change closes the first unresolved audit batch for Vehicle Rental agreements and rate activation.

## Changes

- Enforced optimistic concurrency for draft updates and lifecycle transitions.
- Serialized agreement mutations with database row locks and transactions.
- Added a database-level agreement-kind/party invariant.
- Added the missing tenant-scoped `terminated_by` foreign key.
- Moved the billing timezone default to configuration.
- Made rate activation version-aware and prohibited activation on terminal agreements.
- Replaced historical-period rewriting with explicit overlap rejection.
- Added backend resources and frontend commands that carry `row_version` / `expected_version`.
- Added a focused source contract test and an audit closure matrix.

## Intentional non-goals

- No compatibility aliases for the old unversioned command signatures.
- No implicit truncation or superseding of active rate periods.
- No speculative term-history or bitemporal correction abstraction; those remain separate audited work items.

## Verification performed

- PHP syntax validation for every changed PHP file.
- Focused static contract assertions for concurrency, database constraints, configuration ownership, immutable activation and frontend version propagation.
- Exact branch diff review before publication.

Runtime database and frontend build execution still require a complete project checkout with installed dependencies.
