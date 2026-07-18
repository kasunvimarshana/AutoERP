# Vehicle service job vehicle dropdown model label

Date: 2026-07-18

## Problem

In the vehicle service job form, the vehicle dropdown showed vehicle number plus registration number. That made the dropdown less useful because the registration number was not the most meaningful second identifier for quick selection in this workflow.

## Change

- updated the vehicle lookup label in the vehicle service job form to show vehicle number plus model name;
- updated the vehicle search placeholder text from registration-focused wording to model-focused wording;
- kept the underlying lookup data and search behavior unchanged.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend vehicle selection display in the vehicle service job form.
