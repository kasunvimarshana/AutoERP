# Expense permission catalogue synchronization

Date: 2026-09-22

## Why

The Expense module registered its five permission definitions in the authoritative module registry, but the existing tenant was provisioned before those definitions existed. The sidebar could therefore be visible to a Super Admin while the backend correctly rejected `expense-types.view` because it was absent from the tenant database catalogue.

## What changed

- Ran the existing idempotent `TenantPermissionSeeder`, which uses `TenantAccessProvisioner` inside an explicit tenant execution context.
- Synchronized the current authoritative module permission catalogue for every non-archived tenant.
- Synchronized the protected Super Admin role to the complete active catalogue.
- No custom permission inserts, authorization bypasses, or Expense-specific compatibility patches were introduced.

## Data impact

- Tenant `1` now has all five active Expense permissions.
- Its protected Super Admin role has all five Expense permissions assigned.
- Business, Finance, account, journal, and Expense records were not modified.

## Verification

- Database verification confirmed five active Expense permissions and five protected Super Admin assignments for tenant `1`.
- Focused tenant access provisioning tests passed: 2 tests, 13 assertions.
