# Service invoice pay button permission fix

Date: 2026-07-12

## Problem

The `Pay this invoice` action for vehicle service invoices disappeared even when the route context was correct. The button visibility was still checking the generic Payment-module create permission, while the actual destination route is owned by the vehicle-service payment permission.

## Change

- updated the service-invoice action visibility in the shared invoice detail page to check `vehicle_service.payments.create`;
- kept the existing generic Payment permission check for rental invoice settlement unchanged;
- left the vehicle-service route handoff logic unchanged, only fixing the guard that decides whether the button is shown.

## Verification

- `npm run typecheck`
- `npx vitest run resources/js/modules/invoice/pages/InvoiceLifecycleHandoff.test.tsx --reporter=dot`

## Scope

This is a frontend-only permission-alignment fix for the vehicle service invoice payment handoff button. It restores the expected button visibility without changing the route target or other invoice workflows.
