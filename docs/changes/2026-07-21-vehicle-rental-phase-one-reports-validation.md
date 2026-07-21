# Vehicle Rental Phase 1 report validation follow-up

## Local validation received

The feature-specific and reporting suites passed:

- `php artisan test --filter=VehicleRentalReportDefinitionTest`
- `php artisan test --filter=VehicleRental`
- `php artisan test --filter=Reporting`
- `npm run typecheck -- --pretty false`
- `npm run lint`
- `npm run build`

The full PHP and MySQL suites exposed one architecture failure. The Vehicle Rental financial report query delegated the invoice tenant predicate to a helper method, while the raw-query architecture guard requires the tenant boundary to be declared in the same method that creates the tenant-owned query.

The frontend suite exposed one source-contract failure. The test asserted a single-quoted JSX route literal even though the route value was correctly present with double quotes.

## Corrective changes

- Declare `invoices.tenant_id` directly in `VehicleRentalFinancialReportService::query()`.
- Apply the same tenant boundary to the calculation-source aggregation and vehicle filter subqueries.
- Keep the helper responsible only for organization-unit scope.
- Verify report route values independently of JavaScript quote style.

## Required rerun

```bash
php artisan test --filter=TenantIsolationArchitectureTest
php artisan test
composer test:mysql

npx vitest run resources/js/modules/vehicle-rental/vehicleRentalReports.test.ts
npm run test
```

The pull request remains Draft until these reruns pass.
