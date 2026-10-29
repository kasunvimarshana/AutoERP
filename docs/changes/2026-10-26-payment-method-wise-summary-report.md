# Payment Method Wise Summary report

## Request

Add the previously discussed report for Selling and Vehicle Service, based on the old report's daily payment method view while preserving the current system's credit and receipt accounting.

## Changes

- Added a reporting API and page with date range and receipt source filters. The report uses the current tenant and organization unit context and supports preview, print, PDF, Excel, and CSV exports.
- Show new credit sales on the original invoice date and posted, allocated inbound receipts on the payment date. Keep those measures separate so later collections do not change historical credit sales or count twice as sales.
- Group receipts by their payment method snapshots and currency. When one payment has multiple methods and invoice allocations, distribute each allocation across methods in proportion to the payment lines, assigning any decimal remainder to the last line.
- Include only Selling and Vehicle Service invoice allocations. Unallocated advances and unrelated payment sources are outside this report.

## Verification

- `php artisan test --filter=PaymentMethodWiseSummaryReportTest` passed (9 assertions), including split allocations and credit unchanged after invoice settlement.
- Existing `SalesSettlementBreakdownServiceTest` passed.
- TypeScript typecheck, targeted ESLint, PHP Pint, route registration check, and `git diff --check` passed.
