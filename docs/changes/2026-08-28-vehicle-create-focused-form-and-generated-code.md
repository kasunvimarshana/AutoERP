# Vehicle create focused form and generated code

Date: 2026-08-28

## Why

Vehicle creation needed a smaller primary form and the same visible, editable generated-code behavior used by customer creation. The vehicle-service job quick-create form also needed to capture manufacture year without duplicating storage.

## Changes

- Hid Vehicle Number, Category, and Odometer on the main Vehicle Create form while preserving their backend-generated, nullable, or default values.
- Renamed the Registration field label to Registration Number.
- Removed the Documents and Attributes tabs from initial vehicle creation; their dedicated post-create workspaces remain available.
- Added a tenant-safe `vehicle_code` sequence, authorized code-generation endpoint, collision recovery, and backend fallback generation.
- Displayed the generated vehicle code in both create flows while keeping the field editable and preserving custom codes.
- Hid Vehicle Number, Category, and Odometer in the vehicle-service job quick-create form.
- Mapped the job flow's initial vehicle identifier into Registration Number and added Manufacture Year.
- Reused the existing validated `manufacture_year` database column and persistence path; no schema migration was needed.

## Verification

- `php artisan test app/Modules/Vehicle/Tests/VehicleEngineTest.php` — 14 tests passed, 146 assertions.
- `.\node_modules\.bin\tsc --noEmit`
- `npm run build`
- `git diff --check`
