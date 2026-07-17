# Customer credit profile seeder boundary

Date: 2026-07-16

## Problem

After the authoritative Customer credit profile became deny-by-default, the Customer seeder still wrote the profile through `updateOrCreate()`. That bypassed the Customer-owned policy service and depended on broad model assignment.

Repeated seeding also overwrote an existing credit policy and reset its row version, which is unsafe for a versioned authoritative record.

## Correction

The Customer seeder now uses `CustomerCreditProfileService` and `CustomerCreditProfileData`.

- The default profile is created only when the customer has no existing profile.
- Existing customer credit policy is preserved on repeated seeding.
- Validation, normalization, ownership, and row-version rules remain in the Customer service.
- The production model remains deny-by-default.

## Relationships reviewed

No schema or relationship changed. The seeder now follows the existing Customer-to-credit-profile ownership relationship instead of writing the child record independently.

## Verification

Run:

```bash
git diff --check
php artisan test --filter=CustomerEngineTest
php artisan test --filter=CustomerCreditProfileWriteBoundaryTest
php artisan db:seed --class='Modules\\Customer\\Database\\Seeders\\CustomerSeeder'
php artisan test
composer test:mysql
```
