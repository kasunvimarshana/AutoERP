# Vehicle Rental financial workflow regression fixes

## Trigger

Local verification of merge `3f1f2d7e977ff295f60cba83d4d58e537d4a571b` exposed three independent regressions:

1. `RentalFinancialWorkflowContractTest` failed when executed alone because a plain PHPUnit test called Laravel's `base_path()` helper without booting a Laravel application.
2. Invoice tests still classified `InvoiceType::Rental` as belonging to a retired source module, although Rental financial documents are now an active governed workflow. Only historical Vehicle Finance invoices remain retired.
3. `vehicleRentalNavigation.ts` was rewritten against a non-existent `./types` contract and exported an array instead of the `vehicleRentalNavigationItem` consumed by `tenantWorkspaceNavigation.ts`.

## Root-cause fixes

- Kept the Rental contract test as a true standalone unit test and introduced one project-root source reader based on the test file location. The test no longer depends on Laravel application boot order.
- Updated Invoice owner-module tests to validate the actual lifecycle boundary:
  - Rental invoices are active and governed.
  - Historical Vehicle Finance invoices remain read-only except for settlement updates and cannot be reversed.
- Restored the architecture-owned `NavigationModuleItem` export and valid `navigationTypes` contract.
- Kept the new video-aligned primary navigation:
  - Owner / Supplier Agreements
  - Customer Agreements
  - Daily Running Charts
  - Customer Invoices
  - Owner Settlements
  - Customer Receipts
  - Owner Payments
  - Reports
- Updated frontend integration coverage to validate both the new primary workflow and retained internal operational/audit route entitlements.

## Scope and ownership

- No database schema or relationship changes.
- No production Invoice lifecycle workaround.
- No compatibility alias for the broken navigation export.
- Invoice test corrections remain in Invoice.
- Vehicle Rental navigation corrections remain in Vehicle Rental/frontend navigation ownership.
- The standalone source-contract test remains independent of Laravel runtime state.

## Verification commands

```bash
git diff --check
php artisan test --filter=RentalFinancialWorkflowContractTest
php artisan test --filter=ManualInvoiceServiceTest
php artisan test --filter=VehicleRental
php artisan test
composer test:mysql
npm run typecheck
npm run lint
npx vitest run resources/js/modules/vehicle-rental/vehicleRentalFrontendEntry.test.ts
npx vitest run resources/js/modules/vehicle-rental/rentalFinancialWorkflow.test.ts
npm run test
npm run build
```

Paid tools and GitHub Actions are not required or used.
