# Employee-designation-driven Vehicle Service workforce assignment

Date: 2026-08-05

## Purpose

Removed manually selected operational roles from Vehicle Service workforce assignment. HR employee designations are now the authoritative source, while combo configuration only records whether a labour line must use the supervisor selected on the Job Card.

## Schema foundation

- Replaced `item_bundles.default_workforce_role` with `item_bundles.uses_job_supervisor`.
- Replaced `vehicle_service_job_lines.default_workforce_role` with the snapshotted `vehicle_service_job_lines.uses_job_supervisor` flag.
- Kept combo-specific `unit_cost` on bundle and Job Card child lines as the commission-pool source.
- Kept `vehicle_service_line_employees.role_type` as a backend-owned historical designation-code snapshot, widened it to match HR designation codes, and changed assignment uniqueness to one employee per Job Card line.
- Removed the obsolete controlled Vehicle Service workforce-role enum. No new table or relationship was introduced.

## Job supervisor flow

- Added generic HR employee lookup filters for including or excluding a designation code.
- The Job form supervisor lookup now requests only available employees whose HR designation code is `SUPERVISOR`.
- Backend Job create/update validation independently enforces the same Supervisor designation rule.
- A labour bundle line can be marked `uses_job_supervisor`; combo expansion snapshots that behavior onto the Job Card child line without relying on item names.
- Selecting such a line in Workforce prefills the Job Card supervisor and locks the employee selector to that employee.

## Other workforce assignments

- Removed the Role input and `role_type` request field from the assignment form and API contract.
- Non-supervisor labour lines list available employees excluding the Supervisor designation.
- Backend validation rejects Supervisor-designation employees on normal lines and rejects anyone other than the Job Card supervisor on supervisor lines.
- Employees must have an HR designation before Vehicle Service assignment. The backend normalizes and snapshots its code as the assignment role; later edits that retain the same employee preserve the original snapshot.

## Commission behavior

- Combo labour commission remains `Job Card child quantity × snapshotted unit cost` for the whole line.
- The existing exact split engine still divides that pool across all active assignments on the line and gives any rounding remainder to the final assignment.
- A `uses_job_supervisor` combo line replaces the separate global supervisor commission calculation, preventing double counting.

## Relationship review

- The employee remains related through the existing tenant-safe `employee_id` foreign key.
- Assignment uniqueness no longer includes a client-selected role because one employee cannot hold multiple manually invented roles on the same line.
- HR designation remains owned by HR; Vehicle Service stores only the historical designation-code snapshot needed for reporting.

## Database baseline

The authoritative create migrations changed. Disposable/local databases created from the previous baseline must be rebuilt with `php artisan migrate:fresh` and the Supervisor labour bundle lines must have **Use the supervisor selected on the service job** enabled.

## Verification

- Item and Vehicle Service suites passed: 40 tests, 239 assertions.
- Employee commission and technician work reporting suite passed: 7 tests, 153 assertions.
- Focused HR designation lookup test passed: 1 test, 28 assertions.
- Focused assignment and Job form Vitest suites passed: 13 tests.
- TypeScript type checking, targeted ESLint, PHP syntax checks, diff checks, and the production Vite build passed.
