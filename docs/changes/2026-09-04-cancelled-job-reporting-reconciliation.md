# Cancelled job reporting reconciliation

Date: 2026-09-04

## Request

Correct the two reporting concerns identified after implementing Vehicle Service job cancellation: cancelled jobs retaining an effective contribution in the Vehicle Service Profitability Report, and confirmation that invoice/stock reversals reconcile the ledger-backed P&L and Summary figures.

## Implementation

- Reporting now treats a cancelled Vehicle Service job's revenue, direct cost, commission, gross profit, and margin as zero effective values. The cancelled row and status remain visible for audit and status filtering.
- Original job totals, line quantities/costs, supervisor commission, and employee assignment commissions remain unchanged. Cancellation reporting is a presentation/calculation rule and does not rewrite historical transactions.
- Active/completed jobs continue through the existing profitability calculation without changed formulas.
- Added a report-level regression test covering formatted row values, summarized totals, preserved historical model values, and unchanged active-job calculations.
- Extended the existing real billing-reversal cancellation scenario to assert ledger-backed P&L at all three boundaries:
  - before reversal: invoice revenue and stock issue expense contribute normally;
  - after invoice reversal: revenue is offset while the issued stock cost remains;
  - after job cancellation: the inventory issue reversal offsets that cost and net P&L contribution reaches zero.
- SummaryReportService already consumes FinanceStatementService's P&L result, so no duplicate Summary calculation or workaround was introduced.

The date behavior remains accounting-correct: each immutable reversal affects the reporting period containing its reversal date. Historical periods are not rewritten.

## Verification

- Vehicle Service Profitability report tests: **2 passed, 19 assertions**.
- Reporting framework plus profitability regression: **11 passed, 102 assertions**.
- Vehicle Service cancellation and billing-reversal regressions: **13 passed, 142 assertions**.
- PHP syntax checks, Pint on touched PHP files, and `git diff --check` passed.
- Tests used isolated application test databases. No real jobs, invoices, payments, ledgers, permissions, or relationships were changed.
