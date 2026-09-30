# Namecheap Stellar Plus PDF recovery steps

## Context

The production Purchase Order PDF endpoint returns a sanitized 500 response on a Namecheap Stellar Plus shared-hosting deployment while the focused PDF tests pass locally.

## Hosting-specific recovery sequence

1. In cPanel, open **Select PHP Version**, select PHP 8.2 or a later application-supported PHP version, and enable the extensions required by the application and Dompdf. In particular, verify DOM/XML, MBString, BCMath, PDO MySQL, Sodium, Fileinfo, and ZIP; GD is recommended for image rendering.
2. In the PHP Selector **Options** tab, use a practical PDF runtime limit such as a 256 MB memory limit and a 120-second maximum execution time. Limits should only be treated as the cause when the production log reports a memory or timeout failure.
3. Enable **Manage Shell** and open cPanel **Terminal**. From the Laravel project root, find the production exception using correlation ID `01M1GKJ57AABJHMNK53GPW8BZA` in `storage/logs`.
4. Run Composer's production platform check. If dependencies are absent or stale, install from `composer.lock` with development packages excluded and an optimized autoloader.
5. Clear Laravel's runtime caches, inspect migration status, run the platform health command, and retry the authenticated PDF download.
6. If the log reports a missing `organization_unit_legal_profiles` table or column, perform a reviewed production schema reconciliation. Never run `migrate:fresh` against the production database.

## Important detail

Namecheap can assign PHP versions and configuration differently for addon domains or subdomains. The PHP runtime serving `autoerp.tapromall.com` must have the required extensions; proving only the default account CLI PHP configuration is not sufficient.

## Application follow-up

The Purchase Order PDF action should subsequently be consolidated onto the configured `spatie/laravel-pdf` driver already used by Reporting, with a PDF runtime readiness check. That cleanup improves consistency but must not be used as a workaround for missing hosting extensions, dependencies, permissions, or schema.

No application code or database changes were made.
