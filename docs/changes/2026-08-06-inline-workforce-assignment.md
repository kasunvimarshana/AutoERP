# Inline Vehicle Service workforce assignment

Date: 2026-08-06

## Purpose

Reduced the primary workforce assignment flow to one inline employee selection and confirmation action per labour line.

## Changes

- Replaced the create-assignment drawer with a lazy searchable employee selector and **Add** action directly on each normal labour line.
- The selector loads available non-Supervisor employees on open and excludes employees already assigned to the same line.
- Successful assignment clears the selector, reloads the conflict-aware Job Card snapshot, and keeps the line available for assigning another employee.
- Supervisor-controlled lines display the selected Job Card supervisor and use a single explicit **Assign** action without an employee dropdown.
- Missing Job Card supervisors continue to block supervisor-line assignment with contextual guidance.
- The drawer remains available only for editing advanced assignment details such as hours, rate, status, and employee.
- Existing backend validation, row-version conflict recovery, commission locking, and exact split behavior remain unchanged.

## Database

- No table, column, migration, or stored data was changed.
- Supervisor bundle rows still require the explicit `uses_job_supervisor` configuration; item names are not used to infer behavior.

## Verification

- Focused Workforce and assignment-form Vitest suites passed: 10 tests.
- Focused combo expansion/resource backend test passed: 1 test, 18 assertions.
- TypeScript type checking, targeted ESLint, production build, and `git diff --check` passed.
- Live browser navigation reached the local application login screen without console errors; the isolated browser session was not authenticated, so signed-in Workforce rendering was covered by the focused component tests.
