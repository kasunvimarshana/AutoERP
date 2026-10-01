# Sell PR #87 integration cleanup

## Context

PR #87 remained open because its original base (`integration/sell-worktree-production-20260929`) was obsolete while `sell-module` received two later Vehicle Service / Reporting commits. The Vehicle Rental production release was already complete and is not part of this follow-up work.

## Integration cleanup

- Retargeted PR #87 onto the current released application tree so the real delta is isolated instead of carrying the stale integration history.
- Audited the 23-file delta against merge-base `c4582a5af235371619e1c478e0e60da5f06cbcf4` and the current released tree.
- Confirmed the generated merge preserves the current Vehicle Rental router entries and does not modify any `app/Modules/VehicleRental` runtime file.
- Preserved current Reporting routes and added the Vehicle Service Sales Summary route/page additively.

## Reporting service correction

The new `VehicleServiceSalesSummaryReportService` was corrected before integration:

- Invoice and payment lifecycle/direction values now reference their owning enums instead of duplicating raw strings.
- Vehicle Service source/link identifiers and allocation precision are named constants rather than embedded magic values.
- The empty-invoice payment path now uses an actual empty collection instead of a fabricated invoice ID sentinel.
- Item names come from the canonical Item record instead of aggregating mutable job-line descriptions.
- Organization-unit input is normalized explicitly.

## Verification

- Existing branch verification recorded a passing TypeScript typecheck and diff check for the Vehicle Service Sales Summary UI change.
- The corrected PHP report service was independently syntax-checked with `php -l`; no syntax errors were reported.
- GitHub's generated merge tree was reviewed after the correction: the delta remains limited to the intended Vehicle Service, Reporting, Item/Inventory lookup, shared UI, and documentation files, with no Vehicle Rental runtime file changes.
- No GitHub Actions or paid tooling was used.
- A fresh full dependency-backed suite was not represented as rerun in this cleanup environment.
