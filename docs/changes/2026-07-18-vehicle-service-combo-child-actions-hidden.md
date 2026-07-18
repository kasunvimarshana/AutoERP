# Vehicle service combo child actions hidden

Date: 2026-07-18

## Problem

Combo child rows were visible under the parent pack, but the UI still showed `Edit line` and `Remove line` actions even though those included child rows are controlled by the combo pack and are not independently editable or removable by the backend.

## Change

- hid edit and remove actions for combo child rows in both desktop and mobile job-line views;
- kept combo child rows visible for context while aligning the available UI actions with backend validation.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend vehicle service combo child line actions.
