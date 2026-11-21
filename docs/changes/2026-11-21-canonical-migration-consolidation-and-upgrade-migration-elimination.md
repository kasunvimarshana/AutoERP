# Canonical Migration Consolidation and UpgradeMigration Elimination

**Date:** 2026-11-21  
**Modules:** Customer, Finance, Hr, Inventory, Invoice, Item, Payment, Purchase, Selling, VehicleRental, VehicleService

### Summary of Changes

1. **Migration KISS + Explicit Canonical Consolidation:**
   - Consolidated schema modifications from `UpgradeMigrations` directly into canonical single-table create migrations:
     * `app/Modules/Customer/Database/Migrations/2026_06_12_110002_create_customer_contacts_table.php`: Added `$table->unsignedBigInteger('row_version')->default(1);`.
     * `app/Modules/Finance/Database/Migrations/2026_06_12_070007_create_finance_posting_profiles_table.php`: Added `$table->unsignedBigInteger('row_version')->default(1);`.
     * `app/Modules/Finance/Database/Migrations/2026_06_12_070011_create_finance_ledger_entries_table.php`: Added explicit base amounts `$table->decimal('base_debit', 20, 6)->default('0');` and `$table->decimal('base_credit', 20, 6)->default('0');`.
     * `app/Modules/Inventory/Database/Migrations/2026_06_12_140001_create_inventory_batches_table.php`: Added `$table->unsignedBigInteger('row_version')->default(1);`.
     * `app/Modules/Invoice/Database/Migrations/2026_06_12_150001_create_invoices_table.php`: Added `$table->timestamp('original_printed_at')->nullable();`.
     * `app/Modules/Invoice/Database/Migrations/2026_07_21_140001_create_invoice_document_snapshots_table.php`: Added `$table->json('purchaser_reference_fields')->nullable();`.
     * `app/Modules/Item/Database/Migrations/2026_06_12_090003_create_items_table.php`: Added `$table->decimal('reorder_level', 20, 6)->nullable();`.
     * `app/Modules/Purchase/Database/Migrations/2026_06_12_170001_create_purchase_orders_table.php`: Added `$table->unsignedBigInteger('cancelled_by')->nullable();`, `$table->timestamp('cancelled_at')->nullable();`, and foreign key constraint `purchase_orders_cancelled_by_tenant_fk`.
     * `app/Modules/Selling/Database/Migrations/2026_09_26_100001_create_sales_table.php`: Added `bill_discount_type`, `bill_discount_value`, and `bill_discount_amount`.
     * `app/Modules/Selling/Database/Migrations/2026_09_26_100002_create_sale_lines_table.php`: Added `discount_type`, `discount_value`, `discount_amount`, and `bill_discount_allocated`.
     * `app/Modules/VehicleRental/Database/Migrations/2026_09_12_120001_create_vehicle_rental_customer_usage_charges_table.php`: Added index `vruc_cycle_ix`.
     * `app/Modules/VehicleRental/Database/Migrations/2026_09_12_120002_create_vehicle_rental_owner_usage_charges_table.php`: Added index `vruo_cycle_ix`.
     * `app/Modules/VehicleRental/Database/Migrations/2026_09_08_000003_create_vehicle_rental_running_charts_table.php`: Added `driver_identity_source`, `driver_employee_id`, `driver_name_snapshot`, `driver_reference_snapshot`, indexes `vrc_driver_employee_period_ix` and `vrc_driver_external_period_ix`, and foreign key constraint `vrc_driver_employee_fk`.
     * `app/Modules/VehicleRental/Database/Migrations/2026_09_07_000001_create_vehicle_rental_customer_agreements_table.php`: Added `closed_on`, `supersedes_agreement_id`, unique keys `vrca_identity_uk` and `vrca_successor_uk`, and self-referencing foreign key `vrca_successor_fk`.
     * `app/Modules/VehicleRental/Database/Migrations/2026_09_07_000003_create_vehicle_rental_owner_agreements_table.php`: Added `closed_on`, `supersedes_agreement_id`, unique keys `vroa_identity_uk` and `vroa_successor_uk`, and self-referencing foreign key `vroa_successor_fk`.
     * `app/Modules/VehicleService/Database/Migrations/2026_06_12_190001_create_vehicle_service_jobs_table.php`: Added `line_discount_total`, `job_discount_base`, and `job_discount_amount`.
     * `app/Modules/VehicleService/Database/Migrations/2026_06_12_190003_create_vehicle_service_job_lines_table.php`: Added `batch_id`, `batch_price_revision_id`, indexes `vehicle_service_job_lines_batch_ix` and `vehicle_service_job_lines_batch_price_ix`, and foreign key constraints `vehicle_service_job_lines_batch_id_tenant_fk` and `vehicle_service_job_lines_batch_price_id_tenant_fk`.

2. **Elimination of UpgradeMigrations and Service Provider Simplification:**
   - Deleted all 29 patch migration files across 11 modules and removed their `UpgradeMigrations` directories.
   - Updated all 11 Module Service Providers (`Customer`, `Finance`, `Hr`, `Inventory`, `Invoice`, `Item`, `Payment`, `Purchase`, `Selling`, `VehicleRental`, `VehicleService`) to load migrations exclusively from `__DIR__.'/../Database/Migrations'`.

3. **Test Suite Alignment:**
   - In `app/Modules/Finance/Tests/FinanceAccountRoleBoundaryTest.php`: Updated `test_posting_profile_rule_validity_has_a_deployed_database_upgrade_path()` to assert validity and row versioning in canonical migrations `create_finance_posting_profile_rules_table.php` and `create_finance_posting_profiles_table.php`, and verify provider loads `Database/Migrations`.
   - In `app/Modules/Finance/Tests/FinancePostingIdempotencyBoundaryTest.php`: Updated `test_existing_source_keys_have_a_fail_closed_upgrade_path()` to assert canonical source key behavior on `JournalPostingService.php` and `FinancePostingService.php`.
   - In `tests/Database/PurchaseReleaseVerificationTest.php`: Updated `test_legacy_null_currency_rows_are_preserved_and_audited()` to verify column existence on canonical `purchase_debit_notes` table and test audit logic on null currency values without loading deleted upgrade files.

### Verification Record

- `php artisan test`: 1,259 passed (13,095 assertions) across the entire test suite with 0 failures.
- `vendor/bin/pint --test [modified files]`: 100% passed on all modified files.
- `Tests\Unit\Database\ModuleMigrationBaselineTest`: Verified that all module migrations form a strictly valid one-table-per-file baseline without redundant indexes, raw SQL, or table alterations.
