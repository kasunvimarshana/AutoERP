# Local migration schema reconciliation

`php artisan migrate` on the local MySQL `laravel` database stopped because an older, incompatible `vehicle_rental_running_charts` table already existed. Its migration record was `2026_07_19_300006_create_vehicle_rental_running_charts_table`, whereas the current Vehicle Rental module creates a different running-chart schema in `2026_09_08_000003_create_vehicle_rental_running_charts_table`.

Verified that all nine tables from the obsolete July Vehicle Rental schema had zero rows and no tables outside that legacy group referenced them. Saved the DDL for the two affected empty tables to `storage/app/vehicle-rental-legacy-empty-tables-2026-11-03.sql`, then removed only `vehicle_rental_calculation_sources` (the dependent table) and `vehicle_rental_running_charts`. The current running-chart migration and its dependent migrations then completed.

Two other pending upgrades found schema that was already fully present: `2026_09_29_180001_add_row_version_to_inventory_batches` and `2026_09_29_180002_add_batch_pricing_to_vehicle_service_job_lines`. Checked the exact column types, nullability, defaults, indexes, and foreign keys against those migration files, then recorded those two migrations as applied in the local `migrations` table. No existing business rows or migration records were changed.

Verification: `php artisan migrate --force` completed all remaining migrations, and a second run reported `Nothing to migrate.` All ten Promotion tables, `vehicle_service_job_lines.promotion_discount_amount`, and the new running-chart `vehicle_use_id` column exist. This was a local database repair; no application code or migration files were changed.
