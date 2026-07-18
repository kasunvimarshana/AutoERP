# Vehicle Rental module removal

Date: 2026-07-18

## Decision

The Vehicle Rental implementation was removed after an end-to-end audit found that its workflow, ownership boundaries, financial controls, and user experience did not provide a safe foundation to continue extending.

This is a root-level removal. The application does not retain compatibility routes, hidden feature switches, placeholder services, or duplicated fallback logic for the retired module.

## Removed runtime scope

- Vehicle Rental backend module, provider, routes, commands, models, services, requests, resources, seeders, permissions, tests, and baseline migrations
- Vehicle Rental frontend module, routes, navigation, permissions, API clients, pages, and tests
- Vehicle Rental tenant-plan option and frontend tenant-module catalogue entry
- Vehicle Rental scheduled installment processing
- Vehicle Rental report definitions and report registry integration
- Rental-specific payment list view and new rental payment creation paths
- Vehicle Rental implementation and validation documents that no longer describe active product behavior
- Rental-owned operational and vehicle-finance database tables

## Preserved financial history

The removal does **not** delete generic posted records owned by the financial modules:

- Invoice documents and balances
- Payment documents, allocations, refunds, reversals, and unapplied balances
- Tax transactions
- Finance journals and ledger entries
- Voucher presentation records backed by Payment or Finance

Historical `rental_receipt` and `rental_deposit_requirement` enum values remain readable so existing financial rows can still deserialize and be audited. `PaymentValidationService` explicitly blocks creation of new Vehicle Rental payments.

## Database decommission safety

`2026_07_18_235959_remove_vehicle_rental_module.php` checks every retired table before performing any DDL.

- When every existing rental-owned table is empty, the migration drops the schema in child-first foreign-key order.
- When any retired table contains data, the migration stops before dropping anything and lists the non-empty tables.
- The operational rental data must be exported, independently verified, and explicitly purged before rerunning the migration.
- Posted Invoice, Payment, Tax, and Finance records must not be included in that purge.

The migration is intentionally irreversible. Rollback requires both:

1. restoring a verified pre-removal database backup; and
2. deploying the prior application version.

Do not run this migration against a persistent environment until the archive and restore procedure has been rehearsed.

## Verification contract

`VehicleRentalRemovalContractTest` prevents the retired module, routes, navigation, report registration, or implementation directories from being reintroduced. It also verifies the guarded decommission migration and the historical-payment compatibility boundary.

## Required release gates

```bash
php -l database/migrations/2026_07_18_235959_remove_vehicle_rental_module.php
php -l app/Modules/Payment/Validators/PaymentValidationService.php
php -l app/Modules/Reporting/Services/ReportCatalog.php
php artisan test --filter=VehicleRentalRemovalContractTest
php artisan test
composer test:mysql

npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```

In addition, rehearse the migration against a disposable copy of the latest persistent database and verify that:

- non-empty rental tables block the migration before DDL;
- the approved archive can be restored;
- after explicit rental-data purge, the migration removes all retired tables;
- posted financial history remains queryable through Invoice, Payment, Tax, Finance, Voucher, and generic Reporting screens.
