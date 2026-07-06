# Rental Agreement Regression Hardening

## Context

Followed up after the lessee/lessor agreement and running-chart fixes with full-suite verification. The broad backend run exposed migration-baseline, tenant-scope, and startup-contract regressions that needed root-cause cleanup instead of compatibility patches.

## Changes

- Reworked rental deposit requirements to enforce customer-rental-only deposits through portable tenant/customer foreign-key relationships instead of driver-specific migration SQL.
- Removed database-specific rental agreement and deposit migration statements so module migrations remain explicit, portable create baselines.
- Updated rental agreement integrity contracts to reflect the clean boundary: service validation owns cross-column party semantics, schema owns tenant/customer relationships, and configuration owns billing timezone.
- Removed invoice signed-print global-scope bypasses by running public signed invoice lookups inside the signed tenant execution context.
- Aligned the Composer `dev` process command with the startup readiness contract.
- Folded historical patch migrations into their owning create migrations where safe, including document scanner-column removal and vehicle-service bill-to customer schema, then removed the obsolete patch migration files.

## Verification

- `php artisan test`
- `npm run lint`
- `npm run typecheck`
- `npm run test`
- `npm run build`
- `git diff --check`
