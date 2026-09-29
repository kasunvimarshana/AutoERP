# Vehicle Rental domain knowledge base — 2026-08-27

## Purpose

Create one canonical Vehicle Rental business/domain knowledge base from the supplied four Vehicle Rental videos and the supplied TACGL legacy application/data archive, while keeping the latest `worktree-0.0.8` engineering state and project architecture rules authoritative for implementation boundaries.

## Change

- Added `docs/knowledgebase.md`.
- Consolidated video-observed lessee/lessor agreements, Daily Running Chart, customer billing, owner payable settlement, receipts/payments, adjustments, cheque/bank reconciliation, reports and Vehicle Service integration.
- Added TACGL evidence for vehicle/job/invoice/GL structure, rental charge codes, Outside Work semantics, direct rental-payment GL behavior, duplicated vehicle-identity workaround and legacy security/history risks.
- Distinguished observed evidence, derived integrity requirements, target AutoERP design and business rules that still require explicit confirmation.
- Recorded non-negotiable domain invariants, clean module ownership, concurrency risks, source traceability and minimum future-release acceptance criteria.

## Scope boundary

This is documentation only. It does not restore the retired Vehicle Rental runtime, provider, routes, navigation, migrations, Reporting runtime or Finance seeds. Existing historical Rental financial vocabulary remains governed by the prior safe-removal decision.

TACGL business meaning is preserved as source evidence, but TACGL architecture, duplicate vehicle records, Outside Work workarounds, direct free-text GL rental payments, password/user-level patterns and repair-oriented data handling are explicitly not adopted as AutoERP design.

## Business-decision boundary

The document intentionally leaves unproven policies unresolved, including partial-month proration, included-KM pooling, replacement-day charging, downtime, garage mileage, driver/OT qualification, fuel/repair responsibility, accident/insurance excess, deposit application/forfeiture, exact tax/withholding rounding and maker-checker requirements.

## Verification

- Source branch was rechecked before the write at `3d690433253176375721af5706b232bdb5ff9564`.
- Existing Vehicle Rental safe-removal and regression-cleanup change records were reviewed before creating this documentation.
- `docs/knowledgebase.md` did not exist on the target branch before this change.
- No production code, migration, route, provider, frontend module or test was modified by this documentation change.
