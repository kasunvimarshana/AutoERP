# Tax Invoice Print Layout

Date: 2026-07-07

## Context

Updated the invoice print document to follow the supplied tax-invoice form layout while preserving the recently hardened invoice print data foundation.

## Changes

- Changed the invoice print document title to `Tax Invoice`.
- Rebuilt the Blade print view as an A4 bordered form with invoice date/number, supplier and purchaser sections, delivery/place placeholders, additional information, line table, totals, amount-in-words, and payment-mode rows.
- Kept financial values rendered from `InvoicePrintService` prepared data instead of recalculating totals in Blade.
- Added invoice line references and tax-aware print labels in the invoice print view model.
- Kept unsupported fields such as delivery date, place of supply, amount in words, and payment mode as blank form slots instead of inventing data.
- Added focused test assertions for the tax-invoice layout labels.

## Verification

- `php -l app\Modules\Invoice\Services\InvoicePrintService.php`
- `php -l app\Modules\Invoice\Tests\InvoicePrintServiceTest.php`
- `php artisan test app\Modules\Invoice\Tests\InvoicePrintServiceTest.php`
- `php artisan test app\Modules\Invoice\Tests tests\Feature\Invoice`
- `vendor\bin\pint --dirty --test`
- `git diff --check`
