# Production purchase-order PDF 500 diagnosis

## Request

Investigate why `GET /api/v1/purchase/orders/4/pdf` succeeds locally but returns the generic `UNEXPECTED_ERROR` response after deployment to cPanel.

## Findings

- The response shown by the client is intentionally generic. The global API exception renderer logs the underlying throwable with the request correlation ID and returns `Unexpected server error.` for unhandled 500 errors.
- The Purchase Order endpoint renders its Blade view and instantiates Dompdf directly. The failure therefore can occur while loading the print-only data, rendering the Blade view, constructing Dompdf, parsing the HTML, rendering the PDF, or producing the response.
- The local focused Purchase Order PDF tests pass (2 tests, 12 assertions), including a real `%PDF-` response and tenant scoping. This makes a production runtime, deployed dependency, deployed schema, or production-record-specific difference more likely than a generally broken route contract.
- Dompdf requires the PHP DOM and MBString capabilities. A missing DOM extension in the web PHP runtime, an incomplete/stale Composer `vendor` deployment, an insufficient runtime limit, or an unwritable runtime path must be checked against the production exception rather than guessed from the sanitized JSON response.
- The print-only data path also queries `organization_unit_legal_profiles`; a deployed schema mismatch can therefore fail this endpoint even when the ordinary Purchase Order detail endpoint still loads.
- The Purchase Order controller bypasses the configured `spatie/laravel-pdf` abstraction already used by Reporting. Consolidating PDF rendering behind the configured application driver would remove this inconsistency, but it would not replace missing PHP extensions or repair a stale production schema.

## Required production evidence

Search the production Laravel logs for correlation ID `01M1GKJ57AABJHMNK53GPW8BZA`. The exception class and first application stack frame determine the actual fix:

- `Class "DOMDocument" not found`: enable the DOM/XML extension for the PHP version assigned to the domain.
- `Class "Dompdf\\Dompdf" not found` or missing vendor files: install the locked Composer dependencies on the server and regenerate the optimized autoloader.
- `Allowed memory size` or `Maximum execution time`: raise the web PHP limits and retest with the real Purchase Order.
- `Permission denied`: correct ownership/permissions for Laravel writable directories and configure PDF temporary/cache paths under application storage.
- `SQLSTATE` mentioning `organization_unit_legal_profiles`: reconcile the deployed schema with the release using a reviewed production upgrade; do not run `migrate:fresh` against production.

After correcting the identified deployment issue, clear Laravel's caches and rerun the endpoint smoke test.

## Verification

- `php artisan test app/Modules/Purchase/Tests/PurchaseOrderApiTest.php --filter=purchase_order_pdf` passed: 2 tests, 12 assertions.
- The successful response asserts `Content-Type: application/pdf`, the download filename, and a `%PDF-` file signature.
- The focused successful test also passed locally with PHP memory limits of 64 MB, 128 MB, 192 MB, and 256 MB. A 32 MB PHP process exhausted memory while bootstrapping, so very low cPanel limits remain invalid for this application but are not proven to be the production cause.

No application code or database changes were made.
