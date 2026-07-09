# Vehicle service job draft bill-to mass assignment fix

Date: 2026-07-08

## Problem

Saving a new vehicle service job draft failed with an unexpected server error when the request included `bill_to_customer_id`. The vehicle service job service already wrote that field, but the `VehicleServiceJob` model did not declare an explicit writable attribute list under the project's deny-by-default mass assignment foundation.

## Correction

Fixed the issue in the owning vehicle service job model:

- replaced the implicit `guarded` approach with an explicit `fillable` list for writable job attributes;
- included `bill_to_customer_id` in the declared writable fields so draft create and update flows can persist it correctly;
- strengthened the existing vehicle service engine regression test to assert that the bill-to customer is stored on job creation before invoice and payment flows use it.

## Verification

- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -l app/Modules/VehicleService/Models/VehicleServiceJob.php`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test --filter=test_bill_to_customer_drives_service_invoice_and_payment_party`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test --filter=VehicleServiceEngineTest`
