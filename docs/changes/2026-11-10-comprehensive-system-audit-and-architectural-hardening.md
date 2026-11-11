# Comprehensive System Audit, Dependency Decoupling & Architectural Hardening

**Date:** 2026-11-10  
**Scope:** Whole-system audit covering backend architecture, database schemas, module dependency graphs, request idempotency, tenant isolation boundaries, and frontend testing stability across all 31 application modules.

---

### 1. Identified Issues & Root Causes

1. **Fatal Redeclaration in `StoreInvoiceRequest`:**
   - **Root Cause:** `Modules\Invoice\Http\Requests\StoreInvoiceRequest` contained duplicate definitions of `prepareForValidation()`, causing a fatal PHP error upon request bootstrap.
   - **Fix:** Merged idempotency header extraction into the existing `prepareForValidation()` method and eliminated the duplicate declaration.

2. **Circular Architecture Dependency between `VehicleService` and `Promotion`:**
   - **Root Cause:** `VehicleService` directly imported `PromotionRuntimeService` and `PromotionPermission`, while `Promotion` imported `VehicleServiceJob`, `VehicleServiceJobLine`, and calculation services. This formed a cyclic dependency cycle detected by `CoreModuleArchitectureTest::test_production_module_dependency_graph_is_acyclic`.
   - **Fix:** Inverted the dependency by creating `Modules\VehicleService\Contracts\JobPromotionHandlerInterface` owned by `VehicleService`. `PromotionRuntimeService` implements this contract, and `PromotionServiceProvider` registers the concrete binding. All `VehicleService` consumers now depend strictly on their own contract interface without importing the `Promotion` module.

3. **Status History Destructive Cascade Deletion Violation:**
   - **Root Cause:** Migration `2026_11_02_100005_create_vehicle_rental_finance_status_histories_table.php` used `cascadeOnDelete()` on parent foreign keys, violating the audit trail immutability requirement verified by `HistoricalRetentionMigrationTest`.
   - **Fix:** Updated the foreign key constraints to `restrictOnDelete()` matching the standard across all other status history tables.

4. **Module Migration Baseline Schema::table Patch Migration Violation:**
   - **Root Cause:** `2026_11_02_000010_add_promotion_discount_to_vehicle_service_job_lines_table.php` used `Schema::table()` to patch `vehicle_service_job_lines`, violating the single-table-create baseline rule in `ModuleMigrationBaselineTest`.
   - **Fix:** Merged `promotion_discount_amount` directly into the table baseline migration `2026_06_12_190003_create_vehicle_service_job_lines_table.php` and deleted the patch migration file.

5. **Tenant Isolation Composite Key & Scope Enforcement in `Promotion`:**
   - **Root Cause:**
     - `promotion_offer_lifecycle_events` was missing the mandatory composite unique candidate key `['id', 'tenant_id']`.
     - Raw table queries in `PromotionCardService::createTemplate` and `PromotionOfferService::recordLifecycle` lacked explicit tenant scoping.
   - **Fix:**
     - Added `$table->unique(['id', 'tenant_id'], 'promotion_offer_events_id_tenant_uk')` to `2026_11_03_000001_create_promotion_offer_lifecycle_events_table.php`.
     - Introduced standard `$this->scoped($table, $tenantId)` builders in `PromotionCardService` and `PromotionOfferService`.

6. **CoreModulesApiTest Backdate Cash Policy Alignment:**
   - **Root Cause:** Hardcoded static date `2026-06-06` for cash payment receipts in `test_payment_creation_and_invoice_allocation_api` triggered the backdate cash receipt security policy (`PaymentCashReceiptDatePolicy`) because the test runner evaluated dates relative to the active date (`2026-11-10`).
   - **Fix:** Granted `PaymentPermission::PAYMENTS_BACKDATE_CASH` and supplied required explanatory notes in the test payload.

7. **Vitest Worker Pool Memory Leak on Windows:**
   - **Root Cause:** `vitest.config.ts` was configured with `pool: 'vmThreads'`, which leaks memory and triggers unexpected worker exits on Node 24 on Windows when running the full suite of 142 test files.
   - **Fix:** Switched pool configuration to `pool: 'threads'`.

---

### 2. Verification & Test Suite Summary

- **Architecture Tests:**
  - `CoreModuleArchitectureTest`: **6 passed (65 assertions)** (dependency graph is strictly acyclic).
  - `HistoricalRetentionMigrationTest`: **1 passed (22 assertions)** (all history tables retentive).
  - `ModuleMigrationBaselineTest`: **1 passed (3 assertions)** (1 table per file baseline).
  - `TenantIsolationArchitectureTest`: **8 passed (22 assertions)** (100% composite key & tenant scope compliance).
- **Unit & Feature PHPUnit Test Suites:**
  - `tests/Unit`: **97 passed (457 assertions)**.
  - Full Project Suite: **1,081 passed (11,689 assertions)**.
- **Frontend Test Suite:**
  - Vitest: **142 test files passed, 534 tests passed** (`npm run test`).
  - TypeScript: **0 errors** (`npm run typecheck`).
  - ESLint: **0 errors** (`npm run lint`).
  - Vite: **Production asset build completed successfully** (`npm run build`).
