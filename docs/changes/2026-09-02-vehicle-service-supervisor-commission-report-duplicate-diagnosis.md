# Vehicle Service supervisor commission report duplicate diagnosis

Date: 2026-09-02

## Scope

Diagnosed why local Vehicle Service Job `VSJ-000004` displays the selected Service Supervisor twice in the Employee Commission Report. No application code or data was changed.

## Stored evidence

- Job `vehicle_service_jobs.id = 4` stores supervisor employee `1` and `supervisor_commission_amount = 80.000000`.
- The same job stores `supervisor_commission_type = none` and `supervisor_commission_value = 0.000000` because its effective supervisor commission is supplied by a combo child rather than the separate global supervisor policy.
- Combo child line `3` is a labour line with `uses_job_supervisor = true` and has exactly one active assignment for employee `1`, snapshotted with role `supervisor` and commission `80.000000`.
- Combo child line `4` has the technician assignment for employee `2` and commission `120.000000`.
- Therefore the job contains one supervisor assignment, not two independently created supervisor assignments. Its authoritative commission cost is `200.000000` (`80 + 120`).

## Root cause

Vehicle Service correctly treats the `uses_job_supervisor` combo-child assignment as replacing the standalone global supervisor commission. During job recalculation, its `80.000000` assignment amount is also stored in `vehicle_service_jobs.supervisor_commission_amount` as the job-level calculated summary, while `commission_cost_total` includes the assignment only once.

The Employee Commission Report does not implement that replacement rule:

1. `technicianRows()` selects every line assignment, including assignments whose stored `role_type` is `supervisor`, and hardcodes their `commission_source` to `technician`.
2. `supervisorRows()` independently selects the job-level supervisor whenever `supervisor_commission_amount > 0`.
3. The report combines both queries with `UNION ALL`, so the combo supervisor assignment and its job-level summary are emitted as separate payable commission entries.

This produces the observed totals exactly: technician `120 + 80 = 200`, supervisor `80`, and total `280`, instead of technician `120`, supervisor `80`, and total `200`.

## Correct fix direction

- Make the report respect the Vehicle Service replacement rule and emit one commission fact for a `uses_job_supervisor` combo assignment.
- Classify assignment-backed rows from their backend-owned role snapshot instead of hardcoding every assignment as `technician`.
- Emit the standalone job-level supervisor row only when no active `uses_job_supervisor` combo assignment is the source of that supervisor commission.
- Add a reporting regression test covering a job containing both a normal technician combo child and a `uses_job_supervisor` combo child.

The fix belongs in the Reporting module query because Vehicle Service job recalculation and `commission_cost_total` already avoid double-counting correctly.
