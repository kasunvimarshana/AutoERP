# Rental Allocation Owner Supply Ownership

## Why

Creating an allocation from an owner supply agreement could fail with `An owner supply agreement creates the source owner allocation directly.` The backend rule was correct: owner supply agreements must create an `owner_supplied` allocation directly and must use the supplier's vehicle ownership record, not a company-owned source or another source allocation. The allocation page still defaulted every agreement to a company-owned source and did not pass the matching ownership record.

## What Changed

- Locked owner supply agreements to the `owner_supplied` vehicle source on the allocation page.
- Loaded the matching active supplier vehicle ownership from the Vehicle module for the selected agreement, supplier, vehicle, and agreement period.
- Passed `vehicle_ownership_id` only for owner supply allocations while keeping customer rental source allocation and finance behavior unchanged.
- Displayed the resolved supplier ownership in human-readable form instead of exposing an internal ID.
- Added frontend regression coverage for the owner supply allocation payload.

## Verification

- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx --reporter=dot`
- `npm run typecheck`
- `npm run build`
- `git diff --check`
