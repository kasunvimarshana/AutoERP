# Tenant activation command system audit

Date: 2026-07-01

## Problem

`php artisan tenant:activate` failed after readiness passed because tenant lifecycle audit recording required an HTTP current-user context.

## Root cause

`TenantLifecycleService` already records lifecycle events with a system actor when no request user exists, but the platform audit entry still used `recordPlatform()`, which requires an active platform operator from request context. Console lifecycle commands therefore could not complete even when the tenant was ready.

## Correction

Tenant lifecycle status changes now keep the existing platform-operator audit path for HTTP requests and use an explicit system platform-audit actor for console execution.

## Verification

- `php artisan test tests/Feature/Tenant/TenantActivateCommandTest.php tests/Feature/Database/TenantAccessProvisionerTest.php`
- `php -l app/Modules/Tenant/Services/TenantLifecycleService.php`
- `php -l tests/Feature/Tenant/TenantActivateCommandTest.php`
- Activated local tenant `AUTOERP`; readiness now reports `completed` with no blockers.
