# Vehicle service full service conditional fields

Date: 2026-07-30

## Problem

`Manual job card` and `Next service mileage` should only be relevant when the job `Type` is `Full service`, but they were still available regardless of the selected job type.

## Change

- limited `Manual job card` and `Next service mileage` to `Full service` jobs in the vehicle service job form;
- hide both fields from the UI when the selected type is not `Full service`;
- omit both values from the submitted payload for non-`Full service` jobs;
- updated the form test expectations so `Full service` keeps the fields visible while `Body Wash` hides them.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend vehicle service job form behavior for type-specific optional fields.
