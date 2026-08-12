# Automatic job supervisor workforce assignment

## Why

When a combo expands into a labour child configured to use the job supervisor, the supervisor selected on the service job should appear in Workforce immediately. Requiring a second manual assignment duplicated information already owned by the job and created room for inconsistent employees.

## What changed

- Combo expansion now creates the workforce assignment for every non-cancelled child line marked `uses_job_supervisor` inside the same locked database transaction.
- Changing the service job supervisor synchronizes those system-managed workforce assignments to the newly selected supervisor.
- The backend prevents duplicate manual creation and manual removal of job-supervisor assignments.
- Workforce hides manual Add and Remove actions for system-managed supervisor lines while retaining Edit for operational fields such as hours and status.
- Technician and other labour child lines remain manually assignable.
- The existing child-line commission pool and equal split calculation are unchanged.
- No additional database table or column was required for this synchronization change.

## Verification

- Vehicle Service and Item backend suites: 40 tests, 242 assertions.
- Reporting regression suite: 7 tests, 153 assertions.
- Focused Workforce frontend tests: 7 tests passed.
- TypeScript type-check, targeted ESLint, PHP syntax checks, production build, and `git diff --check` passed.
