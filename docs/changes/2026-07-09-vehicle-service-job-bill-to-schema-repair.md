# Vehicle service job bill-to schema repair

Date: 2026-07-09

## Problem

Saving a new vehicle service job draft still failed after the model-layer bill-to fix because the live `vehicle_service_jobs` table in the existing local database was missing the `bill_to_customer_id` column entirely. The original create-table migration in code already defined that column, but the actual MySQL table had drifted and therefore rejected inserts with `Unknown column 'bill_to_customer_id' in 'field list'`.

## Correction

Added a dedicated vehicle service schema-repair migration for drifted databases:

- creates `bill_to_customer_id` on `vehicle_service_jobs` when it is missing;
- restores the `vehicle_service_jobs_bill_to_customer_ix` index;
- restores the tenant-scoped `vehicle_service_jobs_bill_to_customer_id_tenant_fk` foreign key to `customers`.

Applied the migration locally so the active `autodb` schema now matches the codebase baseline.

## Verification

- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -l app/Modules/VehicleService/Database/Migrations/2026_07_09_120001_repair_vehicle_service_jobs_bill_to_customer_column.php`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan migrate`
- `mysql -h 127.0.0.1 -P 3306 -u root autodb -e "SHOW COLUMNS FROM vehicle_service_jobs LIKE 'bill_to_customer_id';"`
- `mysql -h 127.0.0.1 -P 3306 -u root autodb -e "SHOW CREATE TABLE vehicle_service_jobs\\G"`
