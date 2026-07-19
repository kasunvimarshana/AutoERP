# Vehicle service inspect workforce validation

Date: 2026-07-19

## Problem

Draft vehicle service jobs could be marked as inspected even when they contained labour-assignable job lines but no workforce assignments. That created a broken workflow because the user still needed at least one labour employee assigned in the `Workforce` tab before inspection could meaningfully proceed.

## Change

- added a frontend inspect-time validation in the vehicle service job detail page;
- before moving a draft job to `inspected`, the page now checks the employee-assignable labour lines for active workforce assignments;
- when labour work exists but workforce is still empty, the inspect action is blocked, the user is redirected to the `Workforce` tab, and a validation message is shown;
- added focused tests for the inspect workforce guard.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend validation behavior for moving draft vehicle service jobs into the inspected state.
