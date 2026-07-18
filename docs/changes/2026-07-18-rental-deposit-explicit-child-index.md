# Rental deposit explicit child index

**Date:** 2026-07-18

## Problem

MariaDB 11.4 on the production host rejected the rental deposit agreement-party foreign key even though the referenced agreement columns, types, collation, engine, and parent composite index matched. The failed table definition showed that the host did not automatically create the required composite BTREE index on the child columns during the foreign-key `ALTER TABLE` operation.

## Correction

- added an explicit tenant-first composite index on `rental_deposit_requirements` for `tenant_id`, `agreement_kind`, `customer_id`, and `agreement_id` before declaring the matching foreign key;
- retained the existing agreement-party integrity relationship and delete/update behavior;
- updated the migration contract test to require the explicit child index.

## Relationship review

No relationship changed. The new index is the physical child-side support required for the existing foreign key and matches its column order exactly.

## Verification

```bash
php artisan test tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php
php -l app/Modules/VehicleRental/Database/Migrations/2026_06_12_200022_create_rental_deposit_requirements_table.php
git diff --check
```
