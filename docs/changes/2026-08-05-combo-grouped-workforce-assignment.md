# Combo-grouped Vehicle Service workforce assignment

Date: 2026-08-05

## Purpose

Made employee assignment follow the Job Card's expanded combo labour structure without adding another workforce role or changing the database schema.

## Workforce flow

- Workforce now shows every assignable service or labour line, including unassigned lines, grouped under its human-readable combo parent.
- Standalone service and labour lines remain available in a separate group.
- Assignment starts from the exact labour line, so the line is selected and locked before the employee drawer opens.
- Normal labour lines continue to list any available non-Supervisor employee; no Technician-specific designation restriction was introduced.
- Supervisor labour lines show the supervisor selected on the Job Card and provide an explicit manual **Assign supervisor** action.
- If the Job Card has no supervisor, the UI explains the requirement and disables supervisor-line assignment.
- Existing assignment edit, removal, stale-version recovery, commission-pool locking, and equal split behavior remain unchanged.

## API contract

- Employee-assignable Job Card lines now include a compact `parent_line` summary containing the combo parent's ID, line number, and description.
- The relationship is eager-loaded only by the Workforce endpoint; no table, column, migration, or new relationship was added.

## Verification

- Focused Workforce and assignment-form Vitest suites passed: 9 tests.
- Focused combo expansion/resource backend test passed: 1 test, 18 assertions.
- TypeScript type checking, targeted ESLint, PHP syntax checks, production build, and `git diff --check` passed.
