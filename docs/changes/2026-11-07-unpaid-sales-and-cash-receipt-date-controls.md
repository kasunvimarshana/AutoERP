# Unpaid sales reporting and cash receipt date controls

Date: 2026-11-07

## Request

Show unpaid and partially paid invoices for all customers, including those without an approved credit profile, while preserving the existing explicit credit customer path. Distinguish invoice-date sales, payment-date receipts, and remaining balances. Restrict backdated cash receipt posting. Daily cash closing remains outside this change.

## Changes

- Payment Method Wise Summary now reports the unpaid portion of invoices issued on each date, using the historical invoice balance at that date. It includes Selling and Vehicle Service invoices regardless of credit profile or `settlement_mode`. Same-day partial payments reduce that date's new unpaid amount; receipts remain grouped by payment date and method. The API, export, and UI label this metric `unpaid_sales` / New unpaid sales.
- Summary Reports places all payments received during the selected dates next to the current settlement of selected invoices. The label explains that receipts may belong to earlier invoices. Its existing Cash movement section remains available for method details.
- Payment posting checks cash receipt dates in the workspace timezone. A user posting an earlier-dated cash receipt needs the new `payments.backdate-cash` permission and a reason saved with the payment (service payment metadata or generic payment notes); a future-dated cash receipt is rejected. The Vehicle Service payment form exposes a reason field for earlier dates. Existing credit selection, unpaid invoices, partial payment, and later payment paths remain available.
- No schema or historical transaction records were changed. The tenant permission seeder was run locally, and tenant 1 now has the active `payments.backdate-cash` permission available for assignment. No ordinary user assignment was made as part of this task.

## Verification

- Focused Reporting test covers a direct-mode service invoice with a same-day partial payment and a later split-method receipt. New unpaid sales and receipts appear on their respective dates.
- Local `VSJ-000009` historical balance was LKR 1,900 on November 3 and zero on November 5.
- Focused PHP and frontend tests, TypeScript typecheck, PHP lint, and diff whitespace checks passed. The cash date policy was tested for same-day, unauthorized earlier date, missing reason, and authorized earlier date cases.
