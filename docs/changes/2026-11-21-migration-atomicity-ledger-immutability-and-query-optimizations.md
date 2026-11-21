# Migration Cleanup, Atomicity, Ledger Immutability, and Query Optimizations

**Date:** 2026-11-21  
**Modules:** Invoice, Core, Vehicle, Customer, Supplier, Hr, Inventory, Tax, VehicleService, VehicleRental, Finance, Tenant, User

### Summary of Changes

1. **Migration KISS + Explicit & Schema Cleanup:**
   - In `app/Modules/Invoice/Database/Migrations/2026_07_21_140001_create_invoice_document_snapshots_table.php`: Unrolled the dynamic `foreach (['seller', 'buyer'] as $role)` loop into explicit, line-by-line column definitions for all 14 snapshot columns (`seller_*` and `buyer_*`).
   - Deleted redundant root migration `database/migrations/2026_07_22_000004_allow_vehicles_without_odometer.php` (already canonically defined as nullable in `2026_06_12_120005_create_vehicles_table.php`).
   - Updated `tests/Unit/Vehicle/VehicleOptionalOdometerContractTest.php` to verify the single canonical vehicles baseline migration.
   - In `app/Modules/Core/Providers/CoreServiceProvider.php`: Removed dead migration registration `$this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');`.

2. **Concurrency & Atomicity in Child Collections:**
   - In `app/Modules/Customer/Services/CustomerCategoryService.php`: Wrapped category assignment mutation loop in `DB::transaction()`.
   - In `app/Modules/Supplier/Services/SupplierCategoryService.php`: Wrapped category assignment mutation loop in `DB::transaction()`.
   - In `app/Modules/Hr/Services/`: Wrapped bulk replace mutations in `DB::transaction()` across:
     * `EmployeeSkillService.php`
     * `EmployeeAddressService.php`
     * `EmployeeCertificationService.php`
     * `EmployeeContactService.php`
     * `EmployeeDocumentService.php`
     * `EmployeeLicenseService.php`

3. **Historical Records & Ledger Immutability:**
   - In `app/Modules/Inventory/Models/InventoryMovement.php`: Removed `SoftDeletes` trait and soft-delete usage. Added `booted()` event listener enforcing that ledger movements cannot be deleted, and cannot be updated outside authorized lifecycle transitions (Draft -> Posted posting, draft reversal linking, and Posted -> Reversed reversal). Preserved canonical model relationships (`variant`, `itemVariant`, `allocationIssues`, etc.).
   - In `app/Modules/Inventory/Models/InventoryStockStateChange.php`: Enforced strict immutability throwing `LogicException` on any update or delete.
   - In `app/Modules/Inventory/Models/InventoryValuationConsumption.php`: Enforced immutability throwing `LogicException` on deletes and on any unauthorized updates, while safely allowing reversal linkage and base UOM factor adjustments.
   - In `app/Modules/Tax/Models/TaxTransaction.php`: Enforced strict immutability throwing `LogicException` on any update or delete.
   - In status history models:
     * `app/Modules/Customer/Models/CustomerStatusHistory.php`
     * `app/Modules/Supplier/Models/SupplierStatusHistory.php`
     * `app/Modules/Vehicle/Models/VehicleStatusHistory.php`
     * `app/Modules/VehicleService/Models/VehicleServiceStatusHistory.php`
     * `app/Modules/VehicleRental/Models/AgreementAdjustmentHistory.php`
     * `app/Modules/VehicleRental/Models/VehicleFinanceStatusHistory.php`
     Added `booted()` listeners throwing `LogicException` on updating and deleting.

4. **N+1 Elimination & Query Optimization in SellableBatchLookupService:**
   - In `app/Modules/Inventory/Services/SellableBatchLookupService.php`:
     * Maintained the canonical query with active expiry and stock balance availability scopes.
     * Pre-fetched all batch price revisions for batches in the current page via a single bulk `whereIn('batch_id', $batchIds)` query and grouped them in-memory by `batch_id`.
     * Pre-fetched fallback item prices for distinct `(item_id, variant_id)` tuples, eliminating per-iteration database queries.

5. **Strict Enum Comparisons & Query Builder Literals:**
   - In `app/Modules/Customer/Services/CustomerCreationService.php`: Replaced `$data->status->value === 'active'` with strict enum equality `$data->status === CustomerStatus::Active`.
   - In `app/Modules/Supplier/Services/SupplierCreationService.php`: Replaced `$data->status->value === 'active'` with `$data->status === SupplierStatus::Active`.
   - In `app/Modules/Vehicle/Services/VehicleCreationService.php`: Replaced `$data->status->value === 'active'` with `$data->status === VehicleStatus::Active`.
   - In `app/Modules/Finance/Services/ReversalService.php`: Replaced `'posted'` with `JournalStatus::Posted->value`.
   - In `app/Modules/Tenant/Services/Authentication/TenantAuthenticationDirectory.php`: Replaced raw `'active'` string with `TenantStatus::ACTIVE`.
   - In `app/Modules/User/Services/Provisioning/TenantAccessProvisioner.php`: Replaced raw `'active'` string with `UserStatus::ACTIVE`.

6. **Frontend Test Suite Mock Alignment:**
   - In `resources/js/app/layout/AppHeader.test.tsx` & `resources/js/app/layout/WorkspaceLayout.test.tsx`:
     * Added `useOptionalAuth: () => mockAuth` to the `@/modules/auth/AuthProvider` mock definitions, ensuring complete Vitest mock conformance.

### Verification Record

- Created `tests/Unit/Core/LedgerAndHistoryImmutabilityTest.php` deeply verifying immutability and deletion restrictions across all 12 ledger and history models.
- `php artisan test`: 1,259 passed (13,116 assertions) across the entire test suite with 0 failures.
- `vendor/bin/pint --test`: 100% passed on all modified files.
- `npm run typecheck`: 0 TypeScript errors.
- `npm test`: 169 test files passed (692 tests) with 0 failures.
- `npm run lint`: 0 errors.
