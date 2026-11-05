# Service sales and receipt dates in reports

Date: 2026-11-05

## Context and related report review

`VSJ-000009` has a November 3 service invoice and a November 5 cash receipt. The Service Sales Summary selected invoices by invoice date before looking for payments, so its November 5 view omitted the receipt. Summary Reports showed the current settlement of selected invoices but its labels could be mistaken for cash received within the selected period.

Before changing the report, reviewed Payment Method Wise Summary, Customer Outstanding, Invoice Register, Payment Register, Payment Allocation, Vehicle Service History, Detailed Vehicle Service, Dashboard cash flow, and Finance aging. Payment Method Wise Summary and Dashboard cash flow select receipts by payment date; Customer Outstanding reconstructs balances as of the requested date. Invoice and service history reports present current status against the original document or job date. Finance aging uses current invoice balances even when an earlier as-of date is supplied; that separate historical-aging limitation was not changed here.

## Changes

- Service Sales Summary now selects active, posted service-invoice allocations independently by payment date. An older invoice's receipt appears in the receipt period even when there are no sales for that job in that period. Item filters apply to both sales and receipt lists.
- Added a compact receipt list with payment date, receipt, service job, invoice, method, and allocated amount. The `Payments received in period` total is the sum of these rows. Sales job rows no longer carry a mixed-date collected value.
- Full report exports include a distinct payment-received section; stock and combo exports remain sales-only.
- Summary Reports now labels the settlement strip as the current state of selected sales and calls unpaid balance `Outstanding now`. Its sales document metric reads `Paid to date`. Payment Method Wise Summary explains that credit sales require explicit credit selection.
- No database records, migrations, payment posting, invoice balances, or other report calculations were changed.

## Verification

- Local read-only report run: November 3 revenue 15,200 and receipts 5,700; November 5 revenue 1,900 and receipts 1,900. The November 5 receipt list identifies `RV-2026-000004`, `VSJ-000009`, and Cash.
- Focused backend report and export tests passed: 4 tests, 98 assertions.
- Focused frontend report tests passed after updating the current-balance label; TypeScript typecheck and `git diff --check` passed.
