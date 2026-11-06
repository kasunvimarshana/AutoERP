# Restore receipt-date reporting for service sales

Date: 2026-11-05

## Request

The user approved restoring the previously rolled-back report fix and asked how the November 3 sale and November 5 cash receipt should appear.

## Changes

- Restored the focused Reporting service, export, UI, and test changes from `7bdad27c0`. Service sales remain grouped by invoice date; service invoice receipts are selected independently by payment date, including receipts for older invoices.
- Service Sales Summary now shows a separate payment receipt list and total. Full exports contain the same receipt section. Item filters apply to both the sale and receipt lists.
- Summary Reports now states that its settlement mix is the current state of invoices dated in the selected period, and labels the current unpaid amount and paid-to-date values accordingly. Payment Method Wise Summary clarifies its date and explicit-credit semantics.
- No invoice, payment, allocation, schema, or migration data was changed.

## Verification

- With the current local database, November 3 Service Sales Summary returns eight invoiced jobs, LKR 15,200 revenue, and LKR 5,700 payments received. `VSJ-000009` contributes LKR 1,900 revenue and no receipt that day.
- November 5 returns one invoiced job (`VSJ-000015`), LKR 1,900 revenue, and LKR 1,900 payments received. The receipt row identifies `RV-2026-000004`, `VSJ-000009`, `SERVICE-2026-000007`, and Cash.
- Focused PHP report and export tests passed: six tests, 105 assertions. Focused frontend tests passed: three tests. TypeScript typecheck passed.
