# Vehicle service job list edit icon action

Date: 2026-07-19

## Problem

The vehicle service job list still showed the row-level `Edit` action as a text button, even though similar tables had already been updated to use the clearer icon-style edit action.

## Change

- replaced the vehicle service job list row-level text `Edit` link with the same icon-style edit action used in other updated tables;
- kept the existing view action, permissions, and editable-status rules unchanged.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend row action presentation in the vehicle service job list.
