# Vehicle service job supervisor required frontend validation

Date: 2026-07-19

## Problem

On the vehicle service job create form, users could click `Save draft` without selecting a supervisor and receive no immediate frontend validation feedback on that field.

## Change

- added frontend submit-attempt tracking for the vehicle service job form;
- made the `Supervisor` lookup required in the create/edit form UI;
- when the user clicks `Save draft` without a supervisor, the form now stops submission and shows the supervisor field in an error state with the required validation message.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend supervisor validation behavior in the vehicle service job form.
