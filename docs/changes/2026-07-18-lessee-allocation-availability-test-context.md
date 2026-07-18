# Lessee allocation availability test context

**Date:** 2026-07-18

## Problem

The focused backend regression tests for the Lessee allocation vehicle availability fix queried tenant-owned models without establishing the tenant execution context required by their global scope. The tests therefore returned no vehicles even though the production request path establishes that context through middleware.

## Correction

- Ran the availability query and assertion checks inside the matching tenant execution context.
- Kept the production availability, allocation, ownership, relationship, and UI behavior unchanged.

## Verification

```bash
php artisan test tests/Feature/VehicleRental/RentalAvailabilityServiceTest.php
npx vitest run resources/js/modules/vehicle-rental/components/RentalAvailableVehicleLookupSelect.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx
```

