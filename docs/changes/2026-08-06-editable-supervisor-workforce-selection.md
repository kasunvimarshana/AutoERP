# Editable supervisor workforce selection

Date: 2026-08-06

## Purpose

Allow each supervisor-controlled combo labour line to select its own Supervisor employee while retaining the Job Card supervisor as the fast default.

## Changes

- Every unassigned supervisor labour line now has an editable, lazy searchable employee selector filtered to active, available employees with the Supervisor designation.
- The Job Card supervisor is independently used as the initial selection on every supervisor labour line, including when several combo items are present.
- A supervisor can still be selected when the Job Card has no default supervisor.
- Existing supervisor assignments can be changed from the edit drawer using the same Supervisor-only selector.
- Normal labour lines continue to list non-Supervisor employees.
- Backend workforce validation now authorizes any active employee with the Supervisor designation for a supervisor-controlled line and rejects all other designations. The selected employee no longer has to match the Job Card supervisor ID.
- The Job Card supervisor itself is not changed when a different supervisor is assigned to an individual labour line.

## Database

- No schema, migration, or stored-data change was required.
- The existing explicit `uses_job_supervisor` line flag remains the source of truth for Supervisor-only labour lines.

## Verification

- Focused frontend suites passed: 2 files, 12 tests.
- Focused backend test passed: 1 test, 7 assertions.
- TypeScript typecheck passed.
- Targeted ESLint passed with no warnings.
- PHP syntax checks passed.
- Production frontend build passed.
- `git diff --check` passed.

