# Super Admin readiness collation order

Date: 2026-07-01

## Problem

Tenant readiness blocked activation even though the protected Super Admin role had the complete permission catalogue and the accepted administrator had the expected role and root organization assignment.

## Root cause

`TenantAccessProvisioner::superAdminRoleIsReady()` compared PHP-sorted permission definitions with database-ordered assigned permissions. MySQL collation ordering can differ from PHP string sorting, so a complete permission set was reported as incomplete.

## Correction

Assigned Super Admin permission names are now sorted in PHP before comparison, matching the catalogue readiness check and treating the relationship as a set instead of relying on database collation order.

## Verification

- `php artisan test tests/Feature/Database/TenantAccessProvisionerTest.php`
- `php -l app/Modules/User/Services/Provisioning/TenantAccessProvisioner.php`
- Rechecked local tenant readiness; all checks passed and blockers were empty.
