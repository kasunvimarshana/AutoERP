# Local tenant runtime bootstrap

Date: 2026-07-04

## Problem

The local application could boot, but tenant login still failed because the database did not contain a runtime-ready tenant. `AUTOERP` either did not exist yet or remained a draft tenant without a completed foundation, active subscription, or administrator account.

## Correction

Bootstrapped the local runtime state through the owner-module flows:

- seeded the shared application baseline data;
- created an active platform operator for local platform administration;
- provisioned the `AUTOERP` tenant foundation through tenant onboarding, including:
  - protected root organization;
  - tenant permission catalogue and Super Admin role;
  - active internal authentication provider;
  - active initial tenant administrator account;
- created a local active tenant plan using the active `LKR` currency;
- assigned that plan as the current active subscription for `AUTOERP`;
- activated the tenant lifecycle.

The local tenant administrator now authenticates successfully and tenant readiness reports no blockers.

## Verification

- `php artisan db:seed --no-interaction`
- Verified `AUTOERP` exists and local fallback routing matches the tenant
- Verified onboarding completed with administrator `tenantadmin@gmail.com`
- Verified current active subscription assignment succeeded
- Verified tenant lifecycle status is now `active`
- Verified `TenantReadinessService::inspect()` returns `ready: true`
- Verified `TenantAuthenticationService::login()` succeeds for the seeded tenant administrator
