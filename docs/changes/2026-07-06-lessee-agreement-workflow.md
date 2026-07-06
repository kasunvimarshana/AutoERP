# Lessee Agreement Workflow

## Context

Implemented a focused lessee agreement workflow without creating a duplicate backend aggregate. The existing `customer_rental` rental agreement kind remains the Vehicle Rental module's source of truth for customer-side lessee contracts.

## Changes

- Extended the shared rental agreement presentation layer with a `lessee` page mode mapped to `customer_rental`.
- Added `/vehicle-rental/lessee-agreements`, `/vehicle-rental/lessee-agreements/create`, and `/vehicle-rental/lessee-agreements/:id` routes that reuse the existing agreement pages in lessee mode.
- Filtered the lessee list to customer-side agreements and hid the generic agreement-kind selector in that workflow.
- Made the lessee create screen submit a `customer_rental` agreement with controlled customer selection, no supplier payload, and customer-side deposit support.
- Added lessee agreement entries to the vehicle rental tab navigation, tenant navigation, and route entitlement rules.
- Routed customer-side entry points to the lessee workflow, including reservation conversion, rental customer invoices, rental deposits, and revenue billing agreement links.
- Updated vehicle-rental wording where the workflow is specifically lessee-side.
- Added backend and frontend tests covering customer-side lessee agreement creation and lessee UI payload/filter behavior.

## Verification

- `php artisan test tests/Feature/VehicleRental/RentalAgreementCreateTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx --reporter=dot`
- `npx vitest run resources/js/app/navigation/navigationUtils.test.ts --reporter=dot`
- `npm run typecheck`
- `php -l tests/Feature/VehicleRental/RentalAgreementCreateTest.php`
- `git diff --check`
- `npx eslint resources/js/modules/vehicle-rental/rentalAgreementPresentation.ts resources/js/modules/vehicle-rental/pages/RentalAgreementCreatePage.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementListPage.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementDetailPage.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalBillingPage.tsx resources/js/modules/vehicle-rental/pages/RentalDepositPage.tsx resources/js/modules/vehicle-rental/pages/RentalDashboardPage.tsx resources/js/modules/vehicle-rental/pages/RentalReservationDetailPage.tsx resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.tsx resources/js/modules/vehicle-rental/components/RentalModuleNav.tsx resources/js/app/router.tsx resources/js/app/navigation/navigationConfig.ts resources/js/app/navigation/navigationUtils.test.ts resources/js/app/access/routeEntitlements.ts resources/js/app/access/financeRouteEntitlements.ts resources/js/modules/invoice/pages/InvoiceListPage.tsx` completed with existing React Hooks warnings only.
