# Business Overview Dashboard

## Summary

Implemented a permission-aware Business Overview dashboard that gives authorized users a concise view of revenue, cash flow, service operations, inventory, and current financial obligations. Users without Reporting access continue to see the existing operational quick-start dashboard.

## Changes

- Added the finalized dashboard requirements and data definitions in `docs/dashboard-business-overview-finalized.md`.
- Added `GET /api/v1/reports/dashboard` within the Reporting module, protected by the existing Reporting feature and `reporting.reports.view` permission middleware.
- Added a Reporting-owned dashboard aggregation service using the current tenant and organization-unit execution context.
- Added validated date filters with Today, Last 30 days, This month, This year, and custom date-range options.
- Added KPI cards for today's revenue, period revenue, receivables, payables, active service jobs, and inventory value.
- Added revenue trend, service-job status, cash in/out, inventory health, and receivable/payable aging visualizations without introducing a new chart dependency.
- Added an Action Required panel for overdue receivables/payables, overdue service jobs, low stock, expiring batches, and failed payments.
- Kept monetary calculations and stock threshold comparisons decimal-safe.

## Verification

- Confirmed the dashboard route is registered with authentication, tenant, organization-unit, Reporting feature, and report-view permission middleware.
- Confirmed PHP syntax and Pint checks pass for the new backend files.
- Executed the aggregation service against the local tenant context and confirmed the complete response structure and KPI values are returned.
- Confirmed ESLint passes for the dashboard frontend files.
- Confirmed the production frontend build passes.
- `tsc --noEmit` still reports the pre-existing optional `result.meta` issue in `VehicleServiceHistoryReportPage.tsx`; no dashboard TypeScript errors were reported.
