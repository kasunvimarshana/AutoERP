# Drawer lookup initial validation

Date: 2026-08-23

## Purpose

Prevent the Vehicle Service Add Line drawer from showing an Item validation error immediately when the drawer opens.

## Root cause

The Item lookup intentionally received focus when the create form mounted, but the shared drawer focus management moved focus to the Close button on the next animation frame. Blurring the required, untouched lookup then displayed its required-selection error.

## Changes

- Updated shared drawer focus management to preserve intentional focus that is already inside the drawer.
- Updated shared lookup blur handling so an untouched empty required lookup does not display an error merely because it loses focus.
- Preserved validation for unselected text and form submission attempts.
- Added focused coverage for intentional drawer focus and untouched required lookup blur behavior.

## Verification

- Focused drawer, lookup, and Vehicle Service line-editor tests passed: 12 tests.
- TypeScript typecheck passed.
- Targeted ESLint passed with no warnings.
