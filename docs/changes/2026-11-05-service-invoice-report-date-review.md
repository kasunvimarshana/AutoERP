# Service invoice and receipt report date review

Date: 2026-11-05

## Request

Trace how a November 3 Vehicle Service invoice paid in cash on November 5 appears across reports, using the local database and report code. This was a read-only review of application behavior; no application code or database records were changed.

## Findings

- `VSJ-000009` links to `SERVICE-2026-000007`, an invoice dated November 3 for 1,900. Its cash receipt `RV-2026-000004` and payment allocation are dated November 5. The invoice, payment, and allocation Finance journals have the corresponding business dates.
- Service Sales Summary includes that invoice as November 3 revenue of 1,900 and shows zero collected for that job on November 3. Its November 5 query includes only invoices dated November 5, so the November 5 receipt against the older invoice is absent from the `Collected` total. The screenshot's November 5 sale of 1,900 belongs to `VSJ-000015`.
- Summary Reports selects sales by invoice date but displays their current paid amount, balance, and all active receipt allocations irrespective of receipt date. Its November 3 settlement mix matches the screenshot (cash 2,900, card 500, outstanding 7,600, other paid 4,200); therefore that mix is not an as-of-November-3 cash report.
- Payment Method Wise Summary records explicit Vehicle Service credit only when the invoice link has `settlement_mode = credit`. The reviewed link currently has `direct`, and no November 3 service invoice links have `credit`; the November 5 receipt is still selected by payment date.
- Customer Outstanding reconstructs historical balances from allocation dates: the reviewed invoice was outstanding 1,900 as of November 3 and zero as of November 5. Invoice/Service History/Invoice Balance views use current state. The Finance aging query uses current invoice balances even with an earlier as-of date.

## Verification

- Ran the Service Sales Summary service read-only for November 3 and November 5 and inspected linked invoice, payment, allocation, and journal records.
- Ran the Sales Settlement Breakdown service read-only for both days and reconstructed the invoice's historical outstanding balance for both days.
