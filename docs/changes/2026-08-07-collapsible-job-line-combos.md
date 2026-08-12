# Collapsible Job Lines combo packs

Date: 2026-08-07

## Purpose

Reduce Job Lines visual noise by hiding combo child rows until the user asks to see them.

## Changes

- Combo packs now load collapsed by default in desktop and mobile Job Lines views.
- Added a clear chevron disclosure control with an accessible expand/collapse label and `aria-expanded` state.
- Clicking the combo parent row or disclosure control toggles only that combo's included child rows.
- Each combo maintains independent expanded state, so opening one combo does not close another.
- Edit, remove, stock-issue, and other interactive controls do not trigger the row toggle.
- Standalone lines remain unchanged.
- Inventory-only orphan child rows remain visible when their combo parent is intentionally absent from the API response.
- Extended the shared DataTable row-click contract with an explicit per-row enable predicate instead of placing table interaction logic inside the Vehicle Service module.

## Database and API

- No backend, API, schema, migration, or stored-data changes were required.

## Verification

- Focused VehicleServiceLineEditor suite passed: 4 tests.
- TypeScript typecheck passed.
- Targeted ESLint passed with no warnings.
- Production frontend build passed.
- The configured local URL did not respond during isolated browser verification, so signed-in visual behavior is covered by the focused DOM interaction test rather than a live-page browser pass.
