# Lessee owner-source allocation workflow

**Date:** 2026-07-18

## Problem

A vehicle allocated under a Lessor Agreement is intentionally excluded from the generic available-vehicle lookup because it already has a planned or active allocation. The Lessee allocation form selected that generic path before the vehicle source decision, so users saw no matching vehicle instead of selecting the covering Lessor allocation that supplies the same vehicle.

The backend source-allocation relationship and overlap exception were already correct. The remaining defect was the frontend workflow and its missing regression coverage.

## Correction

- made vehicle source the first allocation decision after the agreement;
- require an explicit Lessee vehicle source when no tenant default is configured;
- show direct vehicle search only for company-owned, financed, and Lessor source-allocation creation flows;
- show the covering Lessor allocation lookup for an owner-supplied Lessee allocation;
- preload eligible Lessor allocations when the lookup opens;
- inherit the vehicle from the selected Lessor allocation and keep it read-only;
- clear stale vehicle, ownership, finance, and source state whenever the source changes;
- reject an empty or unsupported source selection in the form state;
- added a focused frontend regression that selects a Lessor allocation and submits its vehicle, allocation ID, and row version to the existing backend contract.

## Relationship review

No schema or model relationship changed.

`RentalVehicleAllocation::sourceAllocation` and `customerAllocations` remain valid directional relationships. A Lessor allocation is the source capacity and the linked Lessee allocation is its downstream customer deployment. The Lessee allocation inherits the same vehicle and ownership lineage through `source_allocation_id`.

The generic availability query remains strict for unrelated allocations, so this change does not permit double-booking. The existing backend still requires the source allocation to belong to an Owner Supply agreement, use the same vehicle, remain planned or active, and cover the full Lessee allocation period.

## Verification

```bash
npx esbuild resources/js/modules/vehicle-rental/pages/RentalAllocationPage.tsx --loader:.tsx=tsx --format=esm --outfile=/tmp/RentalAllocationPage.js
npx esbuild resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx --loader:.tsx=tsx --format=esm --outfile=/tmp/RentalAllocationPage.test.js
npx vitest run resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx
npm run typecheck -- --pretty false
npm run lint
npm run build
git diff --check
```
