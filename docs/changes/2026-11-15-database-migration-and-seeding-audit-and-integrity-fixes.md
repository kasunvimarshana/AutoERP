# Database Migration & Seeding Audit, Schema & Integrity Fixes

**Date:** 2026-11-15  
**Scope:** `VehicleRental`, `Selling`, `Payment`, `Database / Migrations & Seeders`  
**Focus:** Full end-to-end audit of `php artisan migrate:fresh --seed`, resolution of MySQL InnoDB foreign key constraint errors (errno 150), tenant isolation composite candidate key compliance, baseline table-creation migration consolidation, and cross-module boundary architectural preservation.

---

## 1. Problem Statement & Audit Findings

During a comprehensive end-to-end audit and execution of `php artisan migrate:fresh --seed` against a completely clean database, several structural errors, schema inconsistencies, and architectural boundary violations were identified:

1. **MySQL InnoDB Composite Foreign Key Constraint Error (Errno 150)**:
   - In `2026_11_14_100005_create_vehicle_rental_billing_cycle_items_table.php`, foreign keys referencing `vehicles` and `hr_employees` were configured with `->nullOnDelete()`.
   - In a multi-tenant schema with composite foreign keys `['vehicle_id', 'tenant_id']` referencing `['id', 'tenant_id']`, `tenant_id` is defined as `NOT NULL`.
   - In MySQL InnoDB, an `ON DELETE SET NULL` action is strictly rejected with `errno: 150 "Foreign key constraint is incorrectly formed"` when any participating column in the composite key is `NOT NULL`.
   - Throughout the platform architecture, master reference entities (`vehicles`, `hr_employees`) enforce `restrictOnDelete()` to protect transaction and audit trail integrity.

2. **Missing Composite Identity Candidate Key on Tenant History Table**:
   - In `2026_11_14_100002_create_vehicle_rental_agreement_adjustments_history_table.php`, the table lacked a candidate key `$table->unique(['id', 'tenant_id'], 'vraah_tenant_uk')`.
   - This violated `TenantIsolationArchitectureTest`, which guarantees strict multi-tenant database candidate key isolation across all tenant-owned tables.

3. **Module Migration Baseline Violation in Selling**:
   - The Selling module contained later patch migrations `2026_11_10_180001_add_discounts_to_sales_table.php` and `2026_11_10_180002_add_discounts_to_sale_lines_table.php` directly within `Database/Migrations` containing `Schema::table()`.
   - This violated `ModuleMigrationBaselineTest` and repository architectural standards requiring module migrations in `Database/Migrations` to form a clean, 1-table-per-file fresh baseline. Following repository engineering rules, later `Schema::table()` patches must be merged into the original table-creation migrations.

4. **Cross-Module Circular Boundary Regression**:
   - An Eloquent relation `public function invoice(): BelongsTo` was recently added to `Modules\Payment\Models\PaymentAllocation`, referencing `\Modules\Invoice\Models\Invoice::class`.
   - This violated `FinancePostingContractTest` architectural constraints enforcing that `Payment` services/models do not directly depend on `Invoice` models.
   - Removing the illegal relation while updating `PaymentReceiptService` to query `invoice_sources` with explicit tenant isolation and leveraging the native `invoice_number_snapshot` column restored architectural boundaries without breaking payment receipts.

---

## 2. Key Changes & Implementation Details

### A. Billing Cycle Items Composite Foreign Keys
- **File:** `app/Modules/VehicleRental/Database/Migrations/2026_11_14_100005_create_vehicle_rental_billing_cycle_items_table.php`
- Replaced `->nullOnDelete()` with `->restrictOnDelete()` on `vrbci_veh_fk` (`vehicles`) and `vrbci_drv_fk` (`hr_employees`).
- Aligns with repository standard composite foreign key rules for non-nullable `tenant_id` and safeguards relational audit trails.

### B. Agreement Adjustment History Unique Candidate Key
- **File:** `app/Modules/VehicleRental/Database/Migrations/2026_11_14_100002_create_vehicle_rental_agreement_adjustments_history_table.php`
- Added `$table->unique(['id', 'tenant_id'], 'vraah_tenant_uk');` to satisfy multi-tenant isolation candidate key requirements.

### C. Selling Baseline Migration Consolidation
- **Files Modified:**
  - `app/Modules/Selling/Database/Migrations/2026_09_26_100001_create_sales_table.php`: Added `bill_discount_type`, `bill_discount_value`, and `bill_discount_amount` directly to the baseline table definition.
  - `app/Modules/Selling/Database/Migrations/2026_09_26_100002_create_sale_lines_table.php`: Added `discount_type`, `discount_value`, `discount_amount`, and `bill_discount_allocated` directly to the baseline table definition.
- **Files Removed:**
  - `app/Modules/Selling/Database/Migrations/2026_11_10_180001_add_discounts_to_sales_table.php`
  - `app/Modules/Selling/Database/Migrations/2026_11_10_180002_add_discounts_to_sale_lines_table.php`

### D. Payment / Invoice Module Boundary Preservation & Receipt Engine
- **Files Modified:**
  - `app/Modules/Payment/Models/PaymentAllocation.php`: Removed `invoice()` relationship to maintain clean bounded context between Payment and Invoice modules.
  - `app/Modules/Payment/Services/PaymentReceiptService.php`: Resolved `agreement_reference` via explicit tenant-scoped query on `invoice_sources` (`where('tenant_id', $payment->tenant_id)`), ensuring strict tenant isolation compliance. Replaced dead `$alloc->invoice` property calls with batch tenant-scoped `invoices` table fallback lookup for legacy allocations missing snapshots.
  - `app/Modules/Payment/Tests/PaymentReceiptTest.php`: Added comprehensive unit tests covering receipt generation with allocated invoices, source document resolution, and legacy allocations lacking snapshot data.
  - `app/Modules/VehicleRental/Services/RentalPaymentService.php`: Replaced eager-loading of non-existent `'allocations.invoice'` with `'allocations'`, utilizing the existing `invoice_number_snapshot` column.

---

## 3. Verification Record

- **End-to-End Fresh Migration & Seeding:**
  - `php artisan migrate:fresh --seed` -> **PASSED** (all 124 migrations and 17 seeders completed successfully from scratch in ~4s).
  - `php artisan db:seed` (idempotency verification) -> **PASSED** (17 seeders executed with zero duplicate key errors in 1.1s).
- **Migration Rollback & Reset Verification:**
  - `php artisan migrate:rollback --step=10` -> **PASSED** (reversed cleanly).
  - `php artisan migrate:reset` -> **PASSED** (all migrations reversed cleanly to step 0).
  - `php artisan migrate` -> **PASSED** (migrated forward cleanly).
- **Foreign Key & Integrity Verification:**
  - Audited all 1,087 foreign key constraints across 264 tables via MySQL `INFORMATION_SCHEMA`: 0 orphan records or broken constraints.
  - Composite identity candidate keys verified on all tenant-owned tables.
- **Database & Architecture Tests:**
  - `php artisan test tests/Unit/Database` -> 12 passed (40 assertions, including `ModuleMigrationBaselineTest` and `TenantIsolationArchitectureTest`).
  - `php artisan test app/Modules/Finance/Tests/FinancePostingContractTest.php` -> 2 passed (13 assertions).
- **Domain & Feature Tests:**
  - `php artisan test app/Modules/Payment/Tests` -> 43 passed (218 assertions).
  - `php artisan test app/Modules/VehicleRental/Tests/RentalPaymentTest.php` -> 9 passed (67 assertions).
  - `php artisan test app/Modules/VehicleRental/Tests` -> 274 passed (2,270 assertions).
  - `php artisan test app/Modules/Selling/Tests` -> 8 passed (108 assertions).
- **Full Repository Test Suite:**
  - `php artisan test` -> **1,130 passed, 0 failed** (12,113 assertions).
- **Frontend & TypeScript:**
  - `npm run typecheck` -> **PASSED** (0 errors).
