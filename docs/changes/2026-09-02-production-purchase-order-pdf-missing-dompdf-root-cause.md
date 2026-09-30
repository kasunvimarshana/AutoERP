# Production Purchase Order PDF missing Dompdf root cause

## Evidence

The production Laravel log entry for correlation ID `01M1GKJ57AABJHMNK53GPW8BZA` reports:

`Class "Dompdf\\Dompdf" not found`

The exception is thrown at `PurchaseOrderController.php:137`, where the PDF action instantiates Dompdf. The request reaches the authenticated controller successfully and fails before HTML parsing or PDF rendering.

## Root cause

The deployed Composer dependency tree or generated Composer autoloader is incomplete or stale. This is not a Purchase Order data, authorization, database, memory-limit, or DOM-extension error at the observed failure point. Dompdf is declared in the project lock/dependency configuration and works in the local focused tests, but its class is unavailable to the production autoloader.

## Recovery

- From the deployed Laravel project root, install the exact locked production dependencies with `composer install --no-dev --prefer-dist --optimize-autoloader` (or the equivalent `php composer.phar` invocation on Namecheap).
- Do not run `composer update`; production must use the committed lock file.
- Run Composer's production platform-requirement check and verify `composer show dompdf/dompdf` succeeds.
- Clear Laravel runtime caches and retry the authenticated PDF endpoint.
- If Composer reports a missing PHP extension, enable that extension in Namecheap's PHP Selector and repeat the locked install. Do not work around platform requirements or copy isolated vendor subdirectories manually.

No application code or database changes were made.
