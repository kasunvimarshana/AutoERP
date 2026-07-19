# Vehicle service job list priority count pills

Date: 2026-07-19

## Problem

The new vehicle service job status tabs made switching faster, but users still could not immediately see how many priority jobs were waiting in the most action-oriented states.

## Change

- added tiny count pills to the `Draft`, `Inspected`, and `In progress` vehicle service job list tabs;
- reused the existing server-side list API and pagination metadata to load the three priority totals without changing backend behavior;
- matched the pill colors to the same status badge theme used elsewhere in the app;
- hid the pill when the count is `0`;
- capped displayed values at `9+` when the actual count exceeds `9`.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend vehicle service job list tab labels and priority-count display.
