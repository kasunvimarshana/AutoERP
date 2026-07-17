# Vehicle service payment print button replacement

Date: 2026-07-17

## Problem

The earlier payment-print handoff change kept the print action in a separate success section, but the intended workflow was more specific: once the user completes `Receive, post and allocate`, the main action button itself should change from `Receive, post and allocate` to `Print bill`.

## Change

- updated the vehicle service payment preparation page so the main primary action area now swaps `Receive, post and allocate` with `Print bill` after successful payment creation;
- removed the separate success action buttons so the print step happens in the exact place the user just completed the payment action;
- kept the existing signed invoice print flow and direct print fallback unchanged.

## Verification

- `npm run typecheck`

## Scope

This change refines the frontend vehicle service payment workflow only.
