# Purchase Order continuous line entry

Date: 2026-08-23

## Purpose

Allow users to add several Purchase Order items without reopening the Add Line drawer for each item.

## Changes

- Enabled continuous create behavior specifically for Purchase Order lines while preserving the existing Fast Purchase behavior.
- Kept the Add Line drawer open after a valid line is appended to the Purchase Order draft.
- Reset the line form and focused Item search after a successful add.
- Added a Clear action that resets create-form values and local validation errors without closing the drawer.
- Preserved entered values and kept validation errors inside the drawer when a line is invalid.
- Kept Edit Line behavior unchanged: successful edits close the drawer.
- Kept Close and Cancel immediate, without confirmation.

## Verification

- Focused Purchase line-entry tests passed: 9 tests.
- TypeScript typecheck passed.
- Targeted ESLint passed with no warnings.
- Production Vite build passed.
