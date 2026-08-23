# Vehicle Service continuous job-line entry

Date: 2026-08-23

## Purpose

Allow users to add several Vehicle Service job lines without reopening the Add Line drawer for every item.

## Changes

- Kept the Add Line drawer open after a line is created successfully and reset it to a fresh line form.
- Automatically focused the item search after opening, successfully adding, or manually clearing the create form.
- Added a Clear action that resets create-form values and displayed errors without closing the drawer.
- Preserved the entered form values and kept the drawer open when line creation fails so users can correct and retry the same line.
- Kept Edit Line behavior unchanged: a successful edit still closes the drawer.
- Kept Close and Cancel immediate, without an unsaved-changes confirmation.

## Verification

- Focused Vehicle Service job-line tests passed: 15 tests.
- TypeScript typecheck passed.
- Targeted ESLint passed with no warnings.
- Production Vite build passed.
