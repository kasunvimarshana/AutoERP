# Workforce employee picker docked-panel correction

Date: 2026-09-13

## Clarified requirement

The employee picker must behave like the supplied contact-panel reference. It is not a modal drawer attached to the browser edge. The Workforce screen remains in its normal single-column state until an employee field is clicked; it then exposes a separate contacts column inside the page and resizes the workforce content to make room.

## Changes

- Replaced the portal-based `EmployeePickerDrawer` with an inline `EmployeePickerPanel`.
- Removed the modal backdrop, modal semantics, focus trap, and full-screen overlay behavior.
- Added a responsive two-column Workforce layout while the panel is open: flexible workforce content plus a 22rem employee panel.
- Kept the normal Workforce layout unchanged while no employee field is active.
- Made the employee panel sticky on desktop, independently scrollable, searchable, paginated, and explicitly closable.
- Kept the panel open after choosing an employee so the selection can be reviewed or changed without repeatedly reopening it.
- Stored the active line by ID so refreshed workforce data immediately updates assigned-employee exclusions in the open panel.
- Removed the obsolete drawer component and replaced its tests with inline-panel behavior coverage.

## Verification

- TypeScript compiler passed.
- Focused Workforce and employee-panel tests passed: 9 tests across 2 files.
- Tests confirm the picker is an inline complementary region and not a dialog.
