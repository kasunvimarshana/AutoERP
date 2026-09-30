# Job cancellation report impact review

Date: 2026-09-03

## Request and scope

Read-only application review answering whether cancellation removes job profit and expenses from reports. No application code, records, permissions, or relationships were changed. This record preserves the findings; no additional implementation was requested.

## Confirmed code paths

- Vehicle Service cancellation reverses the original non-zero inventory issue journal through Finance. The issue originally debits cost of goods sold and credits inventory. Finance posts a separate journal with debit/credit amounts swapped; historical amounts remain intact.
- Posted invoice reversal separately reverses its posting plan before the job can be cancelled. Cancelling an unposted invoice does not reverse revenue that was never posted.
- FinanceStatementService calculates P&L from date-filtered ledger debit/credit amounts through AccountBalanceService. SummaryReportService uses that P&L for its income, cost of sales, expenses, and net profit fields. Original and reversal entries offset when both dates are included. Stock issue reversals use the cancellation date; invoice reversals use their selected reversal date. Earlier-period P&L is not rewritten.
- EmployeeCommissionReportService excludes cancelled jobs/lines from payable totals and keeps cancelled commission separately for audit. This is not automatic reversal of a separate payroll expense or cash payout.
- Profit is recalculated as revenue minus expenses, not blindly decreased. Removing a profitable job reduces aggregate profit; removing a loss-making contribution can increase it. Unrelated expenses are not reversed by job cancellation.

## Outstanding report gap

The separate Vehicle Service Profitability Report is not ledger-based. ReportCatalog's `vehicle-service.profitability` definition has no default cancelled-status exclusion, and VehicleServiceProfitabilityCalculator reads original job grand total, line quantity/cost, supervisor amount, and employee assignment commissions without considering cancelled status. Those values intentionally survive cancellation for history, so this report can still include a cancelled job's original financial contribution.

The correction belongs in Reporting: distinguish cancelled historical amounts from effective performance totals without zeroing stored job history. Implementation and regression tests are still required; this review does not claim all reports are cancellation-aware or that live report results were tested.
