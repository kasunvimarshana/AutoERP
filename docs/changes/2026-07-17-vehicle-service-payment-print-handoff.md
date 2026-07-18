# Vehicle service payment print handoff

Date: 2026-07-17

## Problem

After a user clicked `Pay this invoice` from a vehicle service invoice and completed `Receive, post and allocate`, the page redirected away to the payment detail screen. That prevented the user from immediately printing the settled bill from the same workflow.

## Change

- updated the vehicle service payment preparation page to stay on the same screen after successful payment creation;
- added a payment-success section that shows the created payment number, statuses, and a direct `Print bill` action;
- reused the same invoice print flow used on the invoice detail page by requesting the signed print URL first and falling back to the direct invoice print route;
- kept a direct `View payment` link available for users who still want to open the created payment record.

## Verification

- `npm run typecheck`
- attempted `npx vitest run resources/js/modules/vehicle-service/pages/VehicleServicePaymentPreparePage.test.tsx`
- note: the targeted Vitest run is currently blocked by an existing `react-router` ESM/CommonJS test-runtime configuration issue (`Cannot use import statement outside a module`)

## Scope

This change only affects the frontend vehicle service payment preparation flow and its test coverage.
