# Vehicle service next service mileage odometer blur

Date: 2026-07-30

## Problem

The vehicle service job form was auto-filling `Next service mileage` while the user was still typing the `Odometer` value, which made the interaction feel too eager.

## Change

- changed the `Odometer` field behavior so typing updates only the odometer value;
- moved the automatic `Next service mileage = Odometer + 5000` suggestion to the odometer `onBlur` event;
- kept the existing manual override behavior so user-edited next service mileage values are still preserved.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend interaction between the vehicle service job form odometer field and next service mileage autofill.
