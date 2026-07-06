# Local tenant login bootstrap readiness diagnosis

Date: 2026-07-04

## Problem

Local tenant login requests to `/api/v1/auth/login` returned `Unable to resolve tenant context for the active request.` even though local fallback routing was enabled for `AUTOERP`.

## Root cause

The local routing configuration was valid, but the database was not bootstrapped with a usable tenant runtime state. Before seeding, there was no `AUTOERP` tenant record at all. After seeding, the tenant existed, but readiness still failed because the tenant remained `draft` with no current subscription, no initial administrator account, no operational administrator assignment, and no completed foundation state.

## Correction

Ran the shared application seeders to create the baseline tenant-owned reference data and confirmed the remaining blockers through `TenantReadinessService`.

## Verification

- `php artisan db:seed --no-interaction`
- Confirmed tenant `AUTOERP` now exists
- Confirmed local fallback routing is ready for `AUTOERP`
- Confirmed readiness still blocks tenant login until foundation and subscription onboarding are completed:
  - protected root organization not ready
  - exact Super Admin access not ready
  - initial administrator account missing
  - operational administrator missing
  - active plan missing
  - current usable subscription missing
