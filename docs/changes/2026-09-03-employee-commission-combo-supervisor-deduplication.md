# Employee Commission combo supervisor deduplication

Date: 2026-09-03

## Purpose

Fixed the Employee Commission Report counting a `uses_job_supervisor` combo labour commission twice: once from its employee assignment and again from the job-level supervisor commission summary.

## Changes

- Renamed the report's assignment query internally because it contains both technician and combo-supervisor assignments.
- Assignment-backed commission rows now use `supervisor` as their commission source when the owning line is a `combo_child` with `uses_job_supervisor` enabled; all other assignment rows remain technician commissions.
- The standalone job-level supervisor row is excluded when an active `uses_job_supervisor` combo child exists. This applies Vehicle Service's existing rule that the combo supervisor commission replaces the global job supervisor commission.
- Added an internal assignment/job origin to report rows so a supervisor assignment row cannot share a UI row identifier with a job-level supervisor row.
- Kept the job-level `supervisor_commission_amount` unchanged as the calculated costing summary used by Vehicle Service. No schema, stored job, or frontend changes were required.

## Regression coverage

Added a report test containing a technician combo child worth `120.000000`, a supervisor combo child worth `80.000000`, and an otherwise configured global supervisor commission. The report is verified to return exactly two entries with:

- technician commission `120.000000`;
- supervisor commission `80.000000`;
- total commission `200.000000`;
- no repeated `Job supervision` summary row.

## Verification

- PHP syntax checks passed for the changed service and test.
- Focused Employee Commission tests passed: 4 tests, 95 assertions.
- Full `TechnicianWorkReportTest` passed: 8 tests, 168 assertions.
- Changed-file diff whitespace validation passed.
