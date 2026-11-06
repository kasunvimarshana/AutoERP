# Service job report verification after rollback

Date: 2026-11-05

## Request

Recheck how `VSJ-000009` appears for November 3 and November 5 after the Service Sales Summary fix was rolled back. This was a read-only verification; no application code, schema, or database records were changed.

## Findings

- Invoice `SERVICE-2026-000007` is dated November 3 for LKR 1,900. It is currently paid with zero balance. Cash receipt `RV-2026-000004` and its active LKR 1,900 allocation are dated November 5.
- Running the current Service Sales Summary service for November 3 returns eight jobs, LKR 15,200 revenue, and LKR 5,700 collected. `VSJ-000009` contributes LKR 1,900 revenue and zero collected.
- Running it for November 5 returns one job, LKR 1,900 revenue, and zero collected. `VSJ-000009` is absent; the sale belongs to `VSJ-000015`. Thus the November 5 receipt for the older invoice is omitted from this report's collected total.
- Summary Reports settlement mix for November 3 invoices returns cash LKR 2,900, card LKR 500, current outstanding LKR 7,600, and other paid LKR 4,200. The cash figure includes receipts made after November 3. For November 5 invoices it returns zero cash and LKR 1,900 current outstanding.
- The report page required an interactive sign-in in the available browser session, so verification used the same current report services with the selected date parameters and read-only database queries.
