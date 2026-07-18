# Rental deposit agreement identity foundation

**Date:** 2026-07-18

## Problem

Production MariaDB 11.4 continued to reject the rental deposit composite foreign key after both parent and child indexes were made explicit. The constraint included the nullable agreement `customer_id`, while the deposit duplicated that customer even though all deposit operations already use the related agreement as the authoritative party source.

## Correction

- removed the redundant `customer_id` column and customer foreign key from rental deposit requirements;
- replaced the nullable four-column relationship with a non-null composite relationship over tenant, agreement kind, and agreement ID;
- retained database enforcement that deposits belong to the same tenant and only to customer-rental agreements;
- kept customer identity authoritative on the agreement and stopped synchronizing a duplicate deposit value;
- retained pending deposit currency synchronization and the existing lock against changing agreement customer or currency after deposit activity;
- updated focused migration and lifecycle coverage.

## Relationship review

The deposit remains a one-to-one child of an agreement. Removing its duplicate customer relationship eliminates bidirectional identity maintenance and makes the agreement the single source of truth. The composite agreement relationship still prevents attaching a deposit to an owner-supply agreement or an agreement in another tenant.

## Verification

```bash
php artisan test tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php tests/Feature/VehicleRental/RentalAgreementCreateTest.php tests/Feature/VehicleRental/RentalAgreementDraftLifecycleTest.php
php -l app/Modules/VehicleRental/Database/Migrations/2026_06_12_200002_create_rental_agreements_table.php
php -l app/Modules/VehicleRental/Database/Migrations/2026_06_12_200022_create_rental_deposit_requirements_table.php
php -l app/Modules/VehicleRental/Models/RentalDepositRequirement.php
php -l app/Modules/VehicleRental/Services/RentalAgreementService.php
git diff --check
```
