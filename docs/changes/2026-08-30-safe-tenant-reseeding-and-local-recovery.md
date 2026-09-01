# Safe tenant reseeding and local recovery

Date: 2026-08-30

## Problem

Running `php artisan migrate --seed` against an existing local database changed the default tenant from `active` to `draft`. Tenant resolution then rejected every tenant API request with HTTP 422, including login.

## Root cause

`TenantSeeder` used `updateOrCreate` with lifecycle fields in its update values. Every reseed therefore overwrote the existing tenant status, activation timestamp, lifecycle reason, and row version with initial draft values.

## Changes

- Changed the default tenant seed operation to `firstOrCreate`, so seed defaults are applied only when the tenant does not exist.
- Added regression coverage proving that reseeding preserves an existing active tenant's lifecycle status, reason, activation timestamp, and row version.
- Restored the local `AUTOERP` tenant through the validated `tenant:activate` lifecycle command instead of directly updating the database.

## Verification

- Tenant seeder tests passed: 2 tests, 5 assertions.
- Laravel Pint passed for the changed PHP files.
- PHP syntax checks passed.
- Re-running `TenantSeeder` preserved local tenant status `active` and row version `2`.
- A login request with deliberately invalid test credentials reached authentication and returned HTTP 401, confirming tenant resolution no longer returns the previous HTTP 422 error.
- `git diff --check` passed.
