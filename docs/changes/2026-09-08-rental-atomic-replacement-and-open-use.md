# Rental atomic replacement and open-ended use — 2026-09-08

Baseline: `7a9edc1acc1048ac73418ec1780c297241e7d976` on `worktree-0.0.8`.

## Changes and reasoning

Extend the fresh operational implementation with open-ended plans and atomic physical replacement. The source-backed replacement concept is documented in knowledge-base Section 13; transaction boundaries and version/history rules are integrity-derived, not replacement pricing policy. VR-U04 remains unresolved and no replacement charge is generated.

- An omitted planned end requires open-ended customer agreement and owner agreement or Vehicle-owned company coverage. Finite source coverage cannot silently truncate the requested period. Tenant-wide occupancy checks include open-ended plans.
- Replacement uses a guided vehicle/source selector and an actual exchange timestamp, optional separate odometers and a reason. Lock both physical vehicle IDs in order, then return the predecessor, create the successor and record its handover atomically. A missing/wrong source rolls back the old return, new use, both histories and Vehicle status changes.
- Preserve original vehicle IDs and periods. Add one nullable, unique, tenant-safe predecessor reference on the new use. No redundant reverse pointer or duplicate customer/owner identity is introduced. The same customer agreement and exact actual exchange boundary are enforced.
- Known-use return and Running Chart evidence can still lock a soft-deleted physical Vehicle, preserving historical identity. New planning and handover continue through normal visible/available Vehicle admission.
- Update the guided UI, API, knowledge base, operational contract and TODO. Unknown planned end is displayed as open-ended; no billing-day or source-cost rule is inferred.

## Schema boundary

The initial separate schema-extension migration failed the repository's mandatory fresh-baseline architecture gate. Keep that gate intact and integrate the explicit new column/FK/unique constraint and nullability into the original fresh vehicle-use creation migration. No production database was accessed. If an earlier operational revision has already been migrated, this commit is not an automatic upgrade: inspect the deployment schema/journal and prepare an explicit evidence-preserving reconciliation. Do not drop populated tables or erase history.

## Verification

- Replacement regression proves invalid source rolls back the old return and successful company-supplied exchange retains both histories and a common boundary.
- Open-ended regression proves future conflict blocking and rejection of finite source coverage.
- Complete PHP/SQLite suite: **692 tests, 7,864 assertions passed** after the migration correction.
- Rental suite after the historical Vehicle visibility adjustment: **23 tests, 152 assertions passed**.
- Complete Vitest: **79 files, 293 tests passed**; TypeScript, Rental ESLint and production build passed. PHP Pint and whitespace checks passed.
- Free local tools only; commits skip CI. No GitHub Actions or production deployment.

The complete commercial module is still not delivered. Effective successor terms, driver identity/availability, independent calculations and source consumption, Invoice/AP/Payment/Tax/Finance handoffs, deposits, financial reports, complete continuous video/audio review, real MySQL contention and browser/UAT remain outstanding. The protected backup remains unopened. Neither green tests nor physical replacement determines unresolved financial policies.
