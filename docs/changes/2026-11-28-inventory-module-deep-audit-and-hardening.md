# Inventory Module Deep Architectural Audit & Hardening

**Author:** Kasun Vimarshana (<kasunvmail@gmail.com>)  
**Date:** 2026-11-28  
**Scope:** Inventory Module (`app/Modules/Inventory`, `resources/js/modules/inventory`), Cross-Module Integration (`Purchase`, `VehicleService`, `Reporting`), Model Immutability, Dual-Timestamp Architecture, Backdated CRUD, Concurrency Locks, API Routing, and Validation

---

## 1. Context & Motivation

A comprehensive, end-to-end deep audit and review of the Inventory module and its cross-module integrations was conducted across the codebase in accordance with the 12 Core Engineering Axioms in `AGENTS.md`.

Key architectural vulnerabilities, functional gaps, and design discrepancies were identified and resolved at root cause:

1. **Missing CRUD and Workflow Endpoints Across Sub-Resources (Axiom 1 & 4)**:
   - Several primary and secondary inventory resources lacked standard RESTful `show` (by ID), draft `destroy`, and workflow action endpoints (`POST adjustments/{id}/reverse`, `POST transfers/{id}/reverse`).
   - Specifically:
     - `adjustments`: lacked `show`, `destroy`, `reverse`.
     - `transfers`: lacked `show`, `destroy`, `reverse`.
     - `stock-counts`: lacked `show`, `destroy`.
     - `cost-adjustments`: lacked `showAdjustment`, `destroyAdjustment`.
     - `reservations`: lacked `show`, `destroy`.
     - `allocations`: lacked `show`, `destroy`.
     - `batches`: lacked `show`, `destroy`.
2. **Missing Lifecycle Guardrails & Immutability Breaches Across Models (Axiom 6 & 11)**:
   - Posted, approved, or finalized financial and operational inventory transactions were previously susceptible to arbitrary in-place mutation or direct hard deletion.
   - Core sub-models (`InventoryAdjustment`, `InventoryAdjustmentLine`, `InventoryTransfer`, `InventoryTransferLine`, `InventoryStockCount`, `InventoryStockCountLine`, `InventoryCostAdjustment`, `InventoryCostAdjustmentLine`, `InventoryValuationLayer`, `InventoryStockBalance`, `InventoryAllocationIssue`, `InventoryReservation`, `InventoryAllocation`, `InventoryAllocationLine`, and `InventoryBatch`) completely lacked lifecycle `updating` and `deleting` hooks throwing `LogicException` on illegal mutations.
3. **Dual-Timestamp Architecture Violations (Axiom 6)**:
   - `StockCardService` and `InventoryMovementReversalService` previously used a fallback to physical server recording timestamp (`$movement->movement_date ?? $movement->created_at?->toDateString()`), violating the strict separation between civil operational dates and physical server ingestion timestamps.
   - Valuation layers in `AbstractLayerValuationMethod` ordered consumed layers by `layer_date` and physical `created_at` rather than deterministic civil operational date `layer_date` and primary key `id`.
4. **Rigid Same-Warehouse Transfer Constraint Mismatch**:
   - `StockTransferService` correctly permitted intra-warehouse transfers between two different physical locations (`$data->fromWarehouseId === $data->toWarehouseId && $data->fromWarehouseLocationId === $data->toWarehouseLocationId`), but `StoreTransferRequest` and frontend `TransfersTab.tsx` had rigid `from_warehouse_id !== to_warehouse_id` constraints that completely blocked intra-warehouse transfers between locations within the same warehouse.
5. **Magic Strings Violations (Axiom 9)**:
   - `StoreStockCountRequest` and `InventoryStockCount` used raw string literals (`'stock_count'`, `'cycle_count'`) without an authoritative PHP Backed Enum.
6. **Improper Request Re-use for Workflow Actions**:
   - `AdjustmentController::post`, `TransferController::post`, `TransferController::receive`, `TransferController::cancel`, `StockCountController::approve`, `StockCountController::post`, and `ValuationController::postAdjustment` reused `ReleaseQuantityRequest` (which validated allocation/reservation `quantity`) instead of a dedicated workflow request with optional `action_date` and `reason`.
7. **Concurrency & Race Conditions in Serial Tracking**:
   - `SerialTrackingService::create` and `createMany` performed existence checks without database transactions and row-level locks, allowing race conditions under concurrent batch receipts.
8. **Non-Deterministic Pagination Ordering**:
   - Multiple listing and price revision endpoints ordered solely by civil dates or timestamps without primary key tie-breakers (`->orderBy('id')` / `->orderByDesc('id')`), causing non-deterministic page boundaries.
9. **Civil Timezone Inconsistency in Frontend UI**:
   - `StockCardPage.tsx` and `InventoryOverview.tsx` used `Intl.DateTimeFormat().resolvedOptions().timeZone` instead of the centralized `businessTimeZone()` helper from `@/shared/utils/businessDate`.

---

## 2. Changes Made

### Backend

1. **`app/Modules/Inventory/Enums/StockCountType.php` (NEW)**:
   - Created backed enum (`StockCount = 'stock_count'`, `CycleCount = 'cycle_count'`) with `label()` helper, eliminating magic strings.
2. **`app/Modules/Inventory/Http/Requests/StoreStockCountRequest.php`**:
   - Applied `Rule::enum(StockCountType::class)` and defaulted to `StockCountType::StockCount->value`.
3. **`app/Modules/Inventory/Http/Requests/InventoryWorkflowActionRequest.php` (NEW)**:
   - Created dedicated request validating optional civil `action_date` (bounded by current workspace date) and `reason`.
4. **`app/Modules/Inventory/Http/Requests/StoreTransferRequest.php`**:
   - Updated validation rules to permit intra-warehouse transfers between different locations while rejecting identical from/to warehouse and location combinations.
5. **`app/Modules/Inventory/Http/Requests/ReverseConsumableUsageRequest.php`**:
   - Added validation for optional civil `reversal_date` (bounded by current workspace date).
6. **`app/Modules/Inventory/Services/ConsumableUsageService.php` & `ConsumableUsageController.php`**:
   - Added `?string $reversalDate = null` parameter propagating civil reversal date down to inventory movements and finance reversal entries.
7. **`app/Modules/Inventory/Services/StockCardService.php` & `InventoryMovementReversalService.php`**:
   - Removed fallback to physical `created_at`, strictly requiring civil `movement_date`.
   - Removed physical ingestion timestamp `created_at` from `StockCardService` movement query ordering, ordering strictly by civil `movement_date` and primary key `id`.
8. **`app/Modules/Inventory/Strategies/Valuation/AbstractLayerValuationMethod.php`**:
   - Ordered valuation layers strictly by `layer_date` and `id` rather than physical `created_at`.
9. **`app/Modules/Inventory/Services/StockAdjustmentService.php` & `StockTransferService.php` & `InventoryFacade.php`**:
   - Propagated civil `reversalDate` and `reason` down to `StockMovementService::reverse`, ensuring reversal movements carry the legal operational date and reason.
10. **`app/Modules/Inventory/Services/SerialTrackingService.php`**:
   - Wrapped serial creation and duplicate checks in `DB::transaction(..., 3)` with `lockForUpdate()`.
11. **`app/Modules/Inventory/Http/Controllers/BatchController.php` & `StockController.php` & `SellableBatchLookupService.php`**:
   - Added deterministic tie-breakers (`->orderBy('id')` / `->orderByDesc('id')`) across batch, serial, and balance paginations.
12. **Model Immutability & Lifecycle Hooks (Axiom 6)**:
   - Added `booted()` hooks to:
     - `InventoryAdjustment`: Draft-only deletion; prevents mutation of structural attributes when posted/approved/reversed/cancelled.
     - `InventoryAdjustmentLine`: Modifications only permitted when parent adjustment is Draft; tenant/adjustment IDs immutable.
     - `InventoryTransfer`: Draft/Pending-only deletion; prevents mutation of structural attributes once dispatched/received/cancelled/reversed; received transfers strictly immutable in place.
     - `InventoryTransferLine`: Modifications only permitted when parent transfer is Draft/Pending; tenant/transfer IDs immutable.
     - `InventoryStockCount`: Draft-only deletion; posted stock counts strictly immutable in place.
     - `InventoryStockCountLine`: Approved count lines only permit `inventory_adjustment_line_id` mutation; posted count lines strictly immutable.
     - `InventoryCostAdjustment`: Draft-only deletion; posted cost adjustments strictly immutable in place.
     - `InventoryCostAdjustmentLine`: Modifications only permitted when parent cost adjustment is Draft; posted lines strictly immutable.
     - `InventoryValuationLayer`: Deletion blocked entirely (`LogicException`); dimension keys, layer date, and valuation method immutable.
     - `InventoryStockBalance`: Deletion blocked entirely (`LogicException`); dimension keys immutable.
     - `InventoryAllocationIssue`: Deletion blocked entirely (`LogicException`); issue attributes immutable.
     - `InventoryReservation`: Deletion blocked if quantity allocated > 0; structural keys immutable.
     - `InventoryAllocation` & `InventoryAllocationLine`: Deletion blocked if quantity issued > 0; structural keys immutable.
     - `InventoryBatch`: Deletion blocked if batch has active stock balances, historical movements, or active reservations/allocations.
     - `InventorySerialNumber`: Deletion blocked if serial has historical movements, active allocations, or status in `[Reserved, Issued, Sold]`; structural keys immutable.
13. **Controllers & Endpoints Added**:
   - `AdjustmentController`: Added `show`, `destroy`, `reverse`. Updated `post` to use `InventoryWorkflowActionRequest`, passing civil `action_date`.
   - `TransferController`: Added `show`, `destroy`, `reverse`. Updated `post`, `receive`, `cancel` to use `InventoryWorkflowActionRequest`, passing civil `action_date`.
   - `StockCountController`: Added `show`, `destroy`. Updated `approve`, `post` to use `InventoryWorkflowActionRequest`.
   - `ValuationController`: Added `showAdjustment`, `destroyAdjustment`. Updated `postAdjustment` to use `InventoryWorkflowActionRequest`.
   - `ReservationController`: Added `show`, `destroy`.
   - `AllocationController`: Added `show`, `destroy`.
   - `BatchController`: Added `show`, `destroy`.
14. **`app/Modules/Inventory/Routes/api.php`**:
   - Registered corresponding routes for all new actions with appropriate granular permission checks.
15. **`tests/Feature/Inventory/InventoryPermissionBoundaryTest.php`**:
   - Registered all 16 new inventory routes in the permission assertion matrix.
16. **`app/Modules/Inventory/Tests/InventoryCrudAndLifecycleTest.php` (NEW)**:
   - Added dedicated test suite verifying CRUD operations, intra-warehouse transfers, draft deletions, soft deletes, reversal workflows, civil date propagation, serial number lifecycle, and model immutability hooks.

### Frontend

1. **`resources/js/modules/inventory/inventoryApi.ts`**:
   - Added typed methods: `getAdjustment`, `deleteAdjustment`, `reverseAdjustment`, `getTransfer`, `deleteTransfer`, `reverseTransfer`, `getCostAdjustment`, `deleteCostAdjustment`, `getStockCount`, `deleteStockCount`, `getReservation`, `deleteReservation`, `getAllocation`, `deleteAllocation`, `getBatch`, `deleteBatch`.
2. **`resources/js/modules/expense/consumableUsageApi.ts`**:
   - Added optional `reversalDate?: string` parameter to `reverseConsumableUsage`.
3. **`resources/js/modules/inventory/pages/StockCardPage.tsx`**:
   - Replaced `Intl.DateTimeFormat().resolvedOptions().timeZone` with `businessTimeZone()`.
4. **`resources/js/modules/inventory/components/InventoryOverview.tsx`**:
   - Replaced `Intl.DateTimeFormat().resolvedOptions().timeZone` with `businessTimeZone()`.
5. **`resources/js/modules/inventory/components/workflows/TransfersTab.tsx`**:
   - Fixed create button disabled condition to allow intra-warehouse transfers when locations differ.
   - Added `Reverse` button for dispatched, in-transit, and received transfers, and `Delete` button for draft/pending transfers.
6. **`resources/js/modules/inventory/components/workflows/AdjustmentsTab.tsx`**:
   - Added `Reverse` button for posted adjustments and `Delete` button for draft adjustments.
7. **`resources/js/modules/inventory/components/workflows/CountsTab.tsx`**:
   - Added `Delete` button for draft stock counts.
8. **`resources/js/modules/inventory/components/workflows/AllocationsTab.tsx`**:
   - Added `Delete` button for unissued allocations when user has manage permissions.
9. **`resources/js/modules/inventory/components/workflows/ReservationsTab.tsx`**:
   - Added `Delete` button for unallocated reservations when user has manage permissions.
10. **`resources/js/modules/inventory/components/InventoryReferenceTabs.tsx`**:
    - `CostingTab`: Added `Delete` button for draft cost adjustments when user has manage permissions.
    - `TrackingTab`: Added `actions` column with `Delete` button for unused batches when user has manage permissions.

---

## 3. Verification Record

- **Backend Pint Code Style Check**: `php vendor/bin/pint --test` passed with 0 violations.
- **Database Architecture Baseline Tests**:
  - `php artisan test --filter ModuleMigrationBaselineTest`: 1 passed (3 assertions).
  - `php artisan test --filter HistoricalRetentionMigrationTest`: 1 passed (22 assertions).
- **Backend Inventory Test Suite**:
  - `php artisan test app/Modules/Inventory/Tests`: 59 passed (274 assertions).
  - `php artisan test --filter Inventory`: 87 passed (639 assertions).
- **Inventory Permission Boundary Test**:
  - `php artisan test --filter InventoryPermissionBoundaryTest`: 2 passed (183 assertions).
- **Frontend Quality Gates**:
  - `npm run typecheck`: 0 errors.
  - `npm run lint`: 0 errors across all `resources/js` files.
  - `npx vitest run resources/js/modules/inventory`: 3 test files passed (8 tests).

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)

