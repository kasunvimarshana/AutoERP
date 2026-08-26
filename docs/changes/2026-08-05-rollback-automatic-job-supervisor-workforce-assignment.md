# Roll back automatic job supervisor workforce assignment

## Reason

The automatic Workforce assignment introduced in `2026-08-05-automatic-job-supervisor-workforce-assignment.md` did not match the desired workflow and was removed at the user's request.

## What was rolled back

- Adding a combo no longer creates a Workforce assignment automatically for `uses_job_supervisor` child lines.
- Changing the job supervisor no longer updates existing Workforce assignments automatically.
- The automatic-assignment duplicate and deletion guards were removed.
- Workforce again shows **Assign employee** and **Remove assignment** for supervisor labour lines.
- The automatic-assignment-specific frontend and backend test coverage was removed.

## Behavior retained

- Selecting a `uses_job_supervisor` line in the manual assignment dialog still prefills and locks the employee to the supervisor selected on the Job Card.
- HR designation-based validation and role snapshots remain in place.
- Technician and other non-supervisor labour assignments remain manual.
- Combo child commission pools and employee split calculations are unchanged.
- No schema change was made by this rollback.

## Verification

- Focused backend manual supervisor assignment test passed: 1 test, 4 assertions.
- Focused Workforce frontend tests passed: 6 tests.
- TypeScript type-check, PHP syntax checks, and `git diff --check` passed.
