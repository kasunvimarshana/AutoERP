# Summary Reports

## Purpose

Added a period-based summary dashboard inside the existing Reporting module. The report provides the business result shown by the reference application without copying its layout or creating a second reporting feature.

## Navigation

The existing `Reports` navigation entry is now a submenu with:

- `Summary Reports`
- `All Reports`

`Summary Reports` opens `/reports/summary`. The route continues to require an active tenant, organization unit, Reporting module entitlement, and `reports.view` permission.

## Backend sources

`GET /api/v1/reports/summary` requires `date_from` and `date_to` and reads authoritative, tenant- and organization-scoped records:

- Sales and purchases: posted, partially paid, or paid invoices grouped by direction.
- Sales returns: finalized outbound credit notes.
- Purchase returns: posted purchase-return documents.
- Payments received and sent: approved, posted payments grouped by the payment-method snapshots stored on payment lines.
- Income, expenses, cost of sales, and net profit: posted General Ledger entries through the Finance statement service.

Finance statement rows now include their account-category code and name so Reporting can identify the configured `COGS` category without duplicating ledger balance logic.

No summary balances are stored. Every result is rebuilt from source transactions for the requested period.

## Unsupported data

AutoERP does not currently have a payroll transaction source or payroll-specific accounting category. The API returns payroll as unavailable, and the UI explains this state instead of displaying a misleading zero.

## UI

The responsive dashboard includes:

- a current-month date range by default
- a net-result overview
- sales, purchase, and return cards
- a ledger-backed profit bridge
- received and sent payment-method breakdowns
- an explicit payroll availability notice

## Verification

- PHP syntax checks passed for all changed backend files.
- `php artisan route:list --name=api.v1.reports.summary` passed.
- `php artisan test --filter=ReportingPermissionBoundaryTest` passed.
- `php artisan test --filter=FinanceHardeningTest` passed.
- `php artisan test --filter=ReportingFrameworkTest` passed.
- `npm run typecheck -- --pretty false` passed.
- Targeted ESLint passed.
- `npm run build` passed.
- Targeted Vitest could not start because the existing React Router dependency is loaded as ESM through a CommonJS package boundary. No test body ran.

The pre-existing `.gitignore`, `.env`, and `package-lock.json` workspace changes were not modified. `package-lock.json` already contains conflict markers.
