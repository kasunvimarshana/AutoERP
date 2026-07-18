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
- Fresh Finance seeding of rental-only accounts and posting profiles
- Stale Customer and Supplier blocker queries against retired rental tables
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

Historical invoices with rental or vehicle-finance source identities remain available for audit and normal settlement of already-posted receivables/payables. They cannot be edited, approved, posted, cancelled, voided, or reversed after removal because those actions depend on source-restoration workflows that no longer exist. The Invoice module owns and enforces this retired-source boundary.

Historical immutable tenant-plan revisions may still contain the retired module code. Entitlement and resource read paths filter retired codes, while new plan writes remain strict and reject them. Access provisioning deactivates permissions no longer present in the active registry while retaining inactive rows for audit history.

## Database decommission safety

The schema decommission follows the repository's explicit migration-history rules:

- `2026_07_18_235900_preflight_vehicle_rental_removal.php` checks every retired table before any drop migration runs.
- One subsequent migration owns the removal of exactly one retired table.
- Drop migrations execute in child-first foreign-key order.
- Every drop migration rechecks its own table immediately before DDL.

When any retired table contains data, the preflight stops before schema removal. The operational rental data must be exported, independently verified, and explicitly purged before rerunning migrations. Posted Invoice, Payment, Tax, and Finance records must not be included in that purge.

Run the decommission only in a controlled maintenance window:

1. enable application maintenance mode;
2. stop old web workers, queue workers, and the scheduler;
3. create and verify a pre-removal database backup;
4. complete and verify the operational rental archive and approved purge;
5. run the preflight and explicit drop migrations;
6. deploy and start only the new application version;
7. execute post-deployment financial-history smoke tests.

This prevents an old application process from writing to a retired table between preflight and DDL. The migrations are intentionally irreversible. Rollback requires both:

1. restoring the verified pre-removal database backup; and
2. deploying the prior application version.

## Verification contract

`VehicleRentalRemovalContractTest` prevents the retired module, routes, navigation, report registration, or implementation directories from being reintroduced. It also verifies:

- historical Payment compatibility and rejection of new rental payments;
- the retired Invoice source lifecycle boundary;
- complete preflight coverage;
- one explicit guarded drop migration per retired table;
- removal of the earlier multi-table migration design.

`VehicleRentalRuntimeReferenceTest` recursively scans active PHP and TypeScript runtime code for retired provider, route, module, and table references.

## Required release gates

```bash
php -l database/migrations/2026_07_18_235900_preflight_vehicle_rental_removal.php
find database/migrations -name '2026_07_18_2359*_drop_*_table.php' -print0 | xargs -0 -n1 php -l
php -l app/Modules/Payment/Validators/PaymentValidationService.php
php -l app/Modules/Invoice/Constants/RetiredInvoiceSource.php
php -l app/Modules/Invoice/Services/RetiredInvoiceSourcePolicy.php
php -l app/Modules/Invoice/Services/InvoiceStatusService.php
php -l app/Modules/Invoice/Services/InvoiceReversalService.php
php -l app/Modules/Reporting/Services/ReportCatalog.php
php artisan test --filter=VehicleRentalRemovalContractTest
php artisan test --filter=VehicleRentalRuntimeReferenceTest
php artisan test
composer test:mysql

npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```

On Windows PowerShell, validate the decommission migrations with:

```powershell
Get-ChildItem database/migrations/2026_07_18_2359*_*.php | ForEach-Object { php -l $_.FullName }
```

In addition, rehearse the migration sequence against a disposable copy of the latest persistent database and verify that:

- non-empty rental tables block the preflight before DDL;
- the approved archive can be restored;
- after explicit rental-data purge, all retired tables are removed;
- posted financial history remains queryable through Invoice, Payment, Tax, Finance, Voucher, and generic Reporting screens;
- already-posted historical rental invoices can still be settled, while source-dependent lifecycle mutations are rejected;
- Customer and Supplier delete/deactivate workflows do not query retired tables.
