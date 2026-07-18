# Rental deposit foreign-key migration fix

**Date:** 2026-07-18

## Problem

Fresh MariaDB/MySQL migrations failed while creating `rental_deposit_requirements` because its four-column foreign key referenced a redundant agreement index beginning with `id`, which is already the agreement primary key and was not exposed as a usable composite referenced key by the production database variant.

## Correction

- reordered the agreement party identity key to `tenant_id`, `agreement_kind`, `customer_id`, and `id`;
- reordered the matching deposit foreign-key columns to the same tenant-first sequence;
- retained the database invariant that a deposit belongs to the same tenant, customer-rental agreement kind, and customer as its agreement;
- strengthened the migration contract test to verify the exact matching column order.

## Relationship review

No relationship was added or removed. The existing one-to-one agreement deposit relationship and its party-integrity constraint remain intact. Only the physical composite-key order changed so the foreign key is portable and can be created reliably.

## Verification

```bash
php artisan test tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php
php -l app/Modules/VehicleRental/Database/Migrations/2026_06_12_200002_create_rental_agreements_table.php
php -l app/Modules/VehicleRental/Database/Migrations/2026_06_12_200022_create_rental_deposit_requirements_table.php
git diff --check
```
