# Lessee and Lessor Running Chart Data

## Context

Implemented agreement-specific running-chart visibility for the new lessee and lessor workflows. The existing running-chart model remains the source of truth: one physical usage log creates governed revenue and cost usage contexts when the customer allocation is linked to an owner-supply allocation.

## Changes

- Added agreement presentation support for resolving an agreement kind to its commercial running-chart side.
- Added a running-chart panel to rental agreement detail pages.
- Lessee agreement detail pages now load related running-chart data through `agreement_id` and `financial_side=revenue`.
- Lessor agreement detail pages now load related running-chart data through `agreement_id` and `financial_side=cost`.
- The agreement running-chart panel shows the physical usage, allocation, total/net kilometers, billable or payable kilometers, physical status, and commercial fact status.
- Running-chart rows link back to the daily running-chart workspace for the related allocation.
- Added frontend coverage for lessee and lessor agreement detail pages requesting the correct running-chart side.
- Extended the vehicle rental end-to-end contract test to protect the existing backend `agreement_id` plus `financial_side` usage-log filter contract and the agreement detail consumption of it.

## Verification

- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx --reporter=dot`
- `php artisan test tests/Feature/VehicleRental/RentalAgreementCreateTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php`
- `npm run typecheck`
- `git diff --check`
- `php -l tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php`
- `npx eslint resources/js/modules/vehicle-rental/rentalAgreementPresentation.ts resources/js/modules/vehicle-rental/pages/RentalAgreementDetailPage.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.tsx` completed with existing React Hooks warnings only.
