# Management report workspaces and job settlement tracking

Date: 2026-09-05

## Request

Implemented the first business-focused reporting delivery from the reporting workspace review: daily workshop control, job billing and collection tracking, commission presets, vehicle history, customer activity, supplier activity, and a clearer report landing page.

## Backend changes

- Added `Daily Job Operations`, with one row per Vehicle Service job and summary counts for open, completed, awaiting-payment, paid, and cancelled jobs. Rows include human-readable customer, vehicle and supervisor values, assigned employees/hours, parts issue progress, invoice progress, payment progress and balances.
- Added `Job Billing & Collection`, using active Vehicle Service invoice/payment links, non-terminal invoices, invoice balances, and approved/posted receipts. The report classifies jobs as uninvoiced, unpaid, partially paid, paid, or overdue and supports the same states as filters.
- Added `Vehicle Service History`, searchable by vehicle number, registration, job or customer. It presents complaint, diagnosis, work, parts, technicians, supervisor, odometer, next-service mileage and settlement history.
- Added `Customer Activity & Balances` and `Supplier Activity & Payables`. Customer vehicle counts use the authoritative current `vehicle_ownerships` relationship. Financial totals use active invoices and approved/posted payments; cancelled/void/reversed invoices are excluded.
- Added shared Vehicle Service report metrics and a shared job-level report query so billing, settlement, cancellation and organization-scope rules are not recalculated differently in each report.
- Made Detailed Vehicle Service revenue, direct cost, employee incentive, supervisor incentive and estimated contribution cancellation-aware. Cancelled rows stay visible for audit, while their effective management values are zero.
- Registered JSON and shared HTML/print/PDF/XLSX/CSV export routes under the existing Reporting permissions. No database migration or new transactional table was introduced.
- Corrected generic payment reports to show stored party names and references instead of raw party/source IDs. Payment Register now uses the real `document_status` lifecycle and controlled document-status, direction and party-type filters.

## Frontend changes

- Added dedicated report routes and controlled customer, supplier, vehicle, status and billing-state filters. Raw relationship IDs are not exposed.
- Daily Job Operations opens with today selected. Daily Labour Commission opens with today selected, while Labour Incentive Summary opens for the current month. Both commission views use the canonical Employee Commission service rather than a second calculation path.
- Reorganized the Reports landing page into Daily Overview, Vehicle Service, Sales & Collections, Suppliers & Purchases, Inventory Control, and Finance & Tax workspaces. Internal report keys are no longer displayed; the complete catalogue remains available under `Advanced: All Reports`.
- Generic advanced reports now render every declared filter instead of silently hiding filters after the first two.

## Accounting and lifecycle decisions

- A payment record alone does not make a job paid. The paid state is derived from active linked invoice balances, while receipt totals include only approved and posted payment links.
- Cancelled jobs and reversed stock remain visible as history. Cancelled job values do not contribute to effective service revenue, cost, commission or profitability totals.
- Commission reports label values with their existing pending/earned/cancelled lifecycle. No paid-commission concept was invented because no commission settlement/payroll source exists yet.
- Customer and supplier activity reports are read-only reporting projections; they do not mutate invoices, payments, stock, jobs or accounting records.

## Verification

- `php artisan test app/Modules/Reporting/Tests` — 24 tests passed with 367 assertions.
- New integration coverage verifies paid job traversal through daily jobs, billing/collection, vehicle history and customer activity; supplier empty-state SQL; uninvoiced filtering; cancelled effective values; and specialized export routing.
- Reporting page tests verify the task-oriented workspace and the same-day Daily Job preset.
- `npm run typecheck` passed.
- PHP syntax checks, Laravel Pint and `git diff --check` passed for the modified reporting files.
