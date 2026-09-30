# Reporting workspace review and business report roadmap

Date: 2026-09-04

## Request and scope

Reviewed how a user can follow a Vehicle Service job through invoice payment, explained the current Reporting workspace, and identified a business-oriented report set for a service-and-parts operation. This was a read-only design review: no report logic, UI, database records, permissions, or relationships were changed.

## Current paid-job trace

- `Detailed Vehicle Service Report` is the strongest current job-to-settlement view. It supports job/customer/vehicle/item search plus controlled customer and vehicle filters. Rows include job status, invoice/payment progress, invoiced total, paid total, and balance due.
- `Invoice Register` confirms an invoice status and shows amount, paid amount, and remaining balance. `Invoice Balance` provides the settlement balance source view.
- `Payment Register` and `Receipt Voucher Register` confirm the receipt itself. A payment record is not equivalent to a paid job: Vehicle Service changes a job to `paid` only when every active linked invoice has no remaining balance.
- `Employee Commission Report` and `Technician Work` can show linked job, invoice, and payment lifecycle alongside labour/commission values.
- `Summary Reports` provides period totals for finalized sales, received payments, ledger income/expenses, and net profit; it is not a per-job trace.

## Existing matches and gaps

- A same-day filter on Vehicle Service Jobs gives a basic daily job register. Detailed Vehicle Service gives line-level daily data, but there is no purpose-built daily operations summary covering opening/in-progress/completed, parts issue state, billing state, and balances in one job-level row.
- Employee Commission Report already supplies the data for daily labour commissions and grouped incentive summaries: employee/department/designation/supervisor grouping, earned/pending/cancelled values, invoice/payment progress, rankings, and exports. The Employee Incentive endpoint is explicitly a compatibility alias and should not become a second calculation source.
- Vehicle Service Jobs and Detailed Vehicle Service can search by vehicle number, but there is no cohesive Vehicle History report containing odometer, complaints/diagnosis, performed work, parts, technicians, invoices/payments, and next-service information.
- Supplier Report and Customer Report are master-data lists only. They are not transaction/activity statements.
- The catalogue is extensive and technically grouped, but the All Reports UI displays internal keys, generic pages expose at most two declared filters, and some payment reports expose raw party IDs. Key business reports overlap and are difficult for operational users to discover.
- Detailed Vehicle Service currently calculates stored line contribution without a cancelled-job effective-value rule. That cancellation-aware reporting concern needs to be addressed if the detailed report becomes a management total source.

## Recommended clean report set

### Priority Vehicle Service workspace

1. Daily Job Operations — one row per job with workflow, assigned staff, parts issue, invoice progress, payment progress, totals and balance.
2. Job Billing & Collection — job-to-invoice-to-receipt trace with uninvoiced, unpaid, partially paid, paid and overdue views.
3. Daily Labour Commission — a date preset over the canonical Employee Commission service.
4. Labour Incentive Summary — employee/department/designation totals over the same canonical commission source; label amounts as pending/earned, not paid, until a real commission settlement/payroll source exists.
5. Vehicle Service History — controlled vehicle lookup plus vehicle-number search, showing service and financial history.
6. Technician Productivity — jobs, hours, labour value, commission, completion and utilization metrics where scheduled-capacity data exists.
7. Parts Usage & Job Contribution — quantities, recorded costs, revenue, returns/reversals and cancellation-aware effective contribution.
8. Work In Progress / Job Aging — open jobs by age, expected delivery and blocking stage.

### Customer and supplier workspace

9. Customer Activity / Statement — customers, vehicles, jobs, invoices, receipts, credits and outstanding balances.
10. Customer Outstanding / AR Aging — reuse the Invoice balance source with customer-oriented drill-down.
11. Supplier Activity / Statement — PO, GRN, supplier invoice, debit note, return, payment and outstanding trace.
12. Supplier Payables / AP Aging — reuse current AP aging and GRN/GRNI sources with supplier drill-down.

### Daily management and control workspace

13. Daily Business Summary — jobs, invoiced sales, collections, purchases, payments, cost of sales, expenses and net result.
14. Cash & Bank Collection — receipts by method, unapplied money, reversals, cheque/bank status and reconciliation exceptions.
15. Inventory Control — low stock, valuation, aging/expiry and stock movement exceptions, largely curated from existing reports.
16. Profitability — job, customer, vehicle, service type and period views backed by cancellation-aware effective values and ledger reconciliation.

Warranty/comeback, retention, service-due, employee utilization, and paid-commission reports should only be introduced after their authoritative source data and lifecycle are explicitly modelled. They must not be inferred from unrelated fields.

## Proposed UX and architecture

- Replace the undifferentiated catalogue as the primary experience with focused report workspaces: Daily Overview, Vehicle Service, Sales & Collections, Customers, Suppliers & Purchases, Inventory, Finance & Tax, and Audit.
- Keep `All Reports` as an advanced catalogue. Business cards should use human labels and short outcome descriptions, never internal report keys or raw relationship IDs.
- Implement daily and summary variants as named presets/views over canonical services where the business calculation is the same. Do not duplicate commission, invoice-balance, or ledger calculations.
- Dedicated report pages should use controlled customer, supplier, vehicle, employee, item, and status filters, allow same-day/month presets, preserve tenant/organization scoping, and export through the existing shared export path.
- Detail rows should link to the owning job, invoice, payment, vehicle, customer, or supplier screen for drill-down while backend services remain the source of truth.

Implementation sequencing and exact report fields remain to be agreed before code changes. The recommended first delivery is Daily Job Operations, Job Billing & Collection, Daily Labour Commission/Labour Incentive presets, and Vehicle Service History.
