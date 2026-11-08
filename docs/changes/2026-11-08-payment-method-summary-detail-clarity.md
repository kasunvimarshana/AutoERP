# Payment method summary detail clarity

Date: 2026-11-08

## Reason

The daily detail table displayed unpaid invoice amounts and paid receipts together under one generic Amount heading. This made receipt rows look as though they contributed to the New unpaid sales total.

## Change

Grouped expanded detail rows by invoice-date New unpaid sales and payment-date Receipts. Added a short explanation of which summary columns each group belongs to and renamed the detail heading to Row amount. No calculation, transaction, or database record changed.

## Verification

The focused report page test checks both group labels and the explanation. TypeScript typecheck and diff whitespace checks were run.
