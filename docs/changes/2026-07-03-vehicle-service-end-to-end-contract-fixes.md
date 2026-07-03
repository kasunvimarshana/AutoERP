# Vehicle Service End-to-End Contract Fixes

## Why

The Vehicle Service audit found several live contract and workflow mismatches:

- Inventory issue readiness allowed warehouse-wide availability checks, while posting consumes an exact warehouse-location balance.
- Job edit accepted customer complaint text but did not persist it back to the inspection record.
- Vehicle Service line validation allowed any active UOM, even when the selected item did not support that UOM.
- Payment preparation used `expected_job_version`, unlike the rest of the Vehicle Service optimistic-locking API.
- Service invoicing always billed the registered vehicle customer, with no bill-to customer foundation.

## What Changed

- Required `warehouse_location_id` for Vehicle Service stock issue posting and made the inventory issue tab require a controlled Issue Location selection before stock can be selected or posted.
- Kept inventory readiness honest by blocking readiness until both warehouse and location are selected.
- Persisted job-form customer complaint edits into the owning inspection row without wiping inspection notes, diagnosis, or other inspection fields.
- Added `bill_to_customer_id` to service jobs, exposed it in job resources, loaded it in job queries, and used it as the invoice/payment party when present.
- Normalized Vehicle Service payment payloads and requests to `expected_version`.
- Enforced that a service job line UOM must be the item's base UOM or an active item unit.
- Added regression coverage for exact stock issue locations, complaint update/clear behavior, bill-to invoice/payment party selection, item/UOM validation, and payment payload naming.

## Verification

- `php artisan test app/Modules/VehicleService/Tests --stop-on-failure`
- `php artisan test app/Modules/Inventory/Tests --stop-on-failure`
- `php artisan route:list --path=vehicle-service`
- `npx eslint resources/js/modules/vehicle-service/components/VehicleServiceInventoryIssueTab.tsx resources/js/modules/vehicle-service/components/VehicleServiceJobForm.tsx resources/js/modules/vehicle-service/pages/VehicleServicePaymentPreparePage.tsx resources/js/modules/vehicle-service/pages/VehicleServicePaymentPreparePage.test.tsx resources/js/modules/vehicle-service/vehicleServiceTypes.ts`
- `npx vitest run resources/js/modules/vehicle-service/pages/VehicleServicePaymentPreparePage.test.tsx --reporter=dot --silent`
- `npm run build`
- `git diff --check`

`npm run typecheck` and direct `node --max-old-space-size=4096 ./node_modules/typescript/bin/tsc --noEmit --pretty false` both failed before reporting TypeScript diagnostics because Node ran out of heap on this workstation. The production Vite build and targeted ESLint/test checks passed.
