# Business Overview Dashboard — Finalized Specification

Date: 2026-09-14
Status: Approved for implementation

## Goal

Give an authorized tenant user a reliable overall business snapshot in under 15 seconds, with direct drill-down links to the underlying operational records.

## Scope

The first release replaces the static task-center dashboard for users with `reporting.reports.view`. Users without that permission retain a clear operational quick-start view and do not request restricted reporting data.

All values are tenant-scoped and organization-unit-scoped by the existing authenticated context. The organization-unit selector in the application header remains the branch selector and is the single source of truth.

## Date behavior

- Default range: current calendar month.
- Presets: Today, Last 30 days, This month, This year.
- Custom from/to dates are supported.
- Period metrics: selected-period revenue, revenue trend, service-job status, and cash movement.
- Current-position metrics: receivables, payables, active service jobs, inventory value, aging, and action alerts.

## KPI cards

1. Today revenue — finalized outbound non-credit/debit invoices dated today.
2. Period revenue — finalized outbound non-credit/debit invoices inside the selected range.
3. Outstanding receivables — current balance due on finalized outbound invoices.
4. Supplier payables — current balance due on finalized inbound invoices.
5. Active service jobs — jobs not completed, invoiced, paid, or cancelled.
6. Inventory value — current sum of inventory stock-balance total value.

Every KPI provides a human-readable definition and a drill-down destination.

## Charts

### Revenue trend

- Monthly bars for finalized outbound invoice revenue inside the selected period.
- Missing months are returned as zero-value buckets.

### Vehicle Service job status

- Status distribution for jobs dated inside the selected period.
- Uses the Vehicle Service status enum labels.
- Each status links to the filtered Service Job List.

### Cash in vs cash out

- Monthly inbound and outbound posted, approved payments inside the selected period.
- Values come from immutable payment-line amounts, matching the existing Summary Report calculation source.

### Inventory health

- In-stock item count.
- Low-stock item count using each item’s configured reorder level.
- Out-of-stock item count.
- Active batches expiring within 30 days that still have available stock.

### Receivable and payable aging

- Current, 1–30, 31–60, 61–90, and over-90-day buckets.
- Uses the current invoice balance due and due date.
- Receivables and payables remain separate.

## Action Required panel

Prioritized links for:

- Overdue customer invoices.
- Overdue supplier invoices.
- Overdue active Vehicle Service jobs.
- Low-stock items.
- Batches expiring within 30 days.
- Failed payment postings.

Zero-count actions are omitted. If every count is zero, the panel shows a clear all-caught-up state.

## API contract

- Endpoint: `GET /api/v1/reports/dashboard`
- Permission: `reporting.reports.view`
- Feature: Reporting
- Inputs: `date_from`, `date_to`
- Response sections: `period`, `currency_code`, `kpis`, `revenue_trend`, `service_jobs`, `cash_flow`, `inventory_health`, `aging`, and `actions`.

## UX requirements

- Responsive layout for desktop and tablet widths.
- No raw IDs.
- Clear loading, empty, refresh, and error states.
- Charts use accessible labels and tabular numeric values; color is not the only indicator.
- No external chart dependency: lightweight native React/CSS/SVG presentation is sufficient for the first release.
- Drill-down links preserve the relevant status or report context where supported.

## Explicitly deferred

- Gross-profit chart duplication on the dashboard. Authoritative ledger profit remains in Summary Reports.
- Employee commission ranking. This remains in the Employee Commission Report until a dedicated compact dashboard contract is defined.
- Cross-organization-unit aggregation. Users switch organization unit through the existing application header.
- Historical as-of aging. The first release reports current balances grouped by due-date age.
