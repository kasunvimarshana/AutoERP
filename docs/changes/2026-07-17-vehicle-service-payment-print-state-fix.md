# Vehicle service payment print state fix

Date: 2026-07-17

## Problem

After a vehicle service payment was created successfully, the page refreshed the job data. That refresh removed the newly settled invoice from the unpaid invoice list, so the UI lost the selected invoice context before it could replace the action button with `Print bill`.

## Change

- preserved the settled invoice in dedicated frontend state when payment creation succeeds;
- changed the post-payment `Print bill` action and success summary to use that preserved invoice context instead of the refreshed unpaid invoice list;
- kept the existing button-replacement behavior and invoice print flow unchanged.

## Verification

- `npm run typecheck`

## Scope

This change fixes the frontend vehicle service payment completion state only.
