# Finance protected-root seed context

Date: 2026-07-01

## Problem

Fast Purchase failed during Finance posting because the default `inventory_receipt` posting profile had been seeded with `organization_unit_id = null` while operational requests run under the protected root organization unit. The shared seeder context looked up a configurable organization-unit code that defaulted to `HQ`, but tenant organization provisioning creates the protected root with the tenant code.

## Correction

Shared seed context now resolves the active protected root organization unit by `root_marker` instead of a separate bootstrap organization-unit code. The unused bootstrap organization-unit code setting was removed so module seeders use onboarding's organization root as the source of truth.

A Finance seeder regression test now verifies that the default `inventory_receipt` posting profile, Finance accounts, and account-role assignments are seeded for the protected root organization unit.

## Local data repair

The local database had no Finance journal history, so the existing unscoped Finance seed rows were moved to the protected root organization unit in one guarded transaction.

## Verification

- `php artisan test app/Modules/Finance/Tests/FinanceSeederTest.php --stop-on-failure`
- `php artisan test app/Modules/Finance/Tests/FinanceSeederTest.php app/Modules/Purchase/Tests/FastPurchaseTest.php --stop-on-failure`
