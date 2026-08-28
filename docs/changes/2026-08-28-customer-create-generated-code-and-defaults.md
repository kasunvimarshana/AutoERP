# Customer create generated code and defaults

Date: 2026-08-28

## Why

Customer creation needed a faster, focused workflow with an automatically suggested code that users can still edit. The main customer form and the vehicle-service job quick-create flow also needed the same business defaults.

## Changes

- Added an authorized customer-code generation endpoint backed by the tenant-safe `customer_code` sequence.
- Made customer code optional at the API boundary so the backend remains the authoritative fallback generator when a client does not provide a code.
- Preserved user-edited codes and retained tenant-scoped uniqueness validation.
- Added shared frontend customer-create defaults: individual type, active status, and the configured LKR currency resolved by code rather than a hardcoded database ID.
- Loaded and displayed the generated, editable code in both the main customer create form and the vehicle-service job quick customer form.
- Hid Customer Number, Legal Name, and Website only on customer creation; edit screens retain those fields.
- Renamed the Mobile label to WhatsApp Number.
- Removed Categories and Documents from the one-shot customer creation tabs while retaining their dedicated customer relationship workspaces.
- Added feature coverage for generated, user-edited, and backend-fallback customer codes.

## Verification

- `php artisan test tests/Feature/Customer/CustomerCodeGenerationTest.php app/Modules/Customer/Tests/CustomerEngineTest.php tests/Feature/Customer/CustomerListCurrentVehicleTest.php`
- `.\node_modules\.bin\tsc --noEmit`
- `npm run build`
- `git diff --check`
