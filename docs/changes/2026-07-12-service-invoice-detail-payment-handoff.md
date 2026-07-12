# Service invoice detail payment handoff

Date: 2026-07-12

## Problem

After posting a vehicle service invoice, users landed on the invoice detail page but had no direct payment handoff there. To receive the customer payment, they had to navigate back through the service job and use the quick-link payment action.

## Change

- updated the shared invoice detail page to detect posted vehicle service invoices with remaining balance;
- when the current user can create payments, the invoice detail actions now show a `Pay this invoice` button for service invoices;
- the button opens the existing Payment-owned invoice settlement workflow at `/payments/create?invoice_id={id}`, which preloads the invoice and allocates the payment to it.

## Verification

- `npm run typecheck`
- `npx vitest run resources/js/modules/invoice/pages/InvoiceLifecycleHandoff.test.tsx --reporter=dot`

## Scope

This is a frontend-only workflow improvement for vehicle service invoices on the invoice detail page. It keeps the existing payment ownership boundaries intact while removing the need to return to the service job just to receive payment.
