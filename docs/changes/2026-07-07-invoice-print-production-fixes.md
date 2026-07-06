# Invoice Print Production Fixes

## Context

Implemented the invoice print audit findings recorded in `2026-07-07-invoice-print-audit-findings.md`.

## Changes

- Added an invoice-owned `InvoicePrintService` to prepare print-safe document data from scoped invoice records.
- Moved invoice print/PDF lookup and rendering behind `InvoiceController` methods instead of duplicated route closures.
- Removed debug invoice routes and repeated PDF generation logic from `routes/web.php`.
- Rebuilt the invoice print Blade view so it renders prepared document data only.
- Stopped recalculating financial totals in the Blade template.
- Switched print totals to persisted invoice values: subtotal, discounts, tax, charges, adjustments, grand total, paid, credits, and balance due.
- Switched party rendering to immutable invoice party snapshots and scoped organization/tenant display data.
- Removed sample/static legal text and unsupported schema fields from the print document.
- Validated invoice tenant and organization-unit scope before issuing signed public print/PDF links.
- Required public signed print/PDF URLs to carry the signed organization-unit scope for scoped invoices.
- Added invoice print contract tests covering persisted totals, immutable party snapshots, scoped signed link generation, and public signed route scope rejection.

## Verification

- `php -l app/Modules/Invoice/Http/Controllers/InvoiceController.php`
- `php -l app/Modules/Invoice/Services/InvoicePrintService.php`
- `php -l app/Modules/Invoice/Tests/InvoicePrintServiceTest.php`
- `php artisan test app/Modules/Invoice/Tests tests/Feature/Invoice`
- `php artisan test`
- `vendor\bin\pint --dirty --test`
- `git diff --check`
