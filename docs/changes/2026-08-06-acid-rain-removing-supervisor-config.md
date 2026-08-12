# ACID RAIN REMOVING S supervisor configuration

Date: 2026-08-06

## Reason

The Supervisor component of `ACID RAIN REMOVING S` was configured as a normal labour line, so Workforce used the non-Supervisor employee endpoint and did not default the Job Card supervisor.

## Correction

- Updated item bundle row 7 (`ACID RAIN REMOVING S` -> `Supervisor`) through `ItemBundleService` within tenant 1.
- Changed only `uses_job_supervisor` from `false` to `true`; all other bundle values were preserved.
- Future Job Cards now snapshot this component as Supervisor-controlled, use the Supervisor-only employee lookup, and default the Job Card supervisor in the editable selector.
- Existing test Job Card snapshots were intentionally left unchanged as requested.

## Database

- No schema or migration change was required.
- This was a source item-bundle configuration correction only.

## Verification

- Confirmed both `ACID RAIN REMOVER L` and `ACID RAIN REMOVING S` Supervisor bundle rows now have `uses_job_supervisor = true`.
- Confirmed existing Job 10 lines remained unchanged.

