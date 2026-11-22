# Systemwide CRUD Referential Integrity Blockers & Route Authorization Hardening

**Date:** 2026-11-22  
**Directives:** `/compact` `/learn` `/plan` `/boost` `/goal`  
**Scope:** Whole-repository audit and remediation of CRUD operations, referential integrity deletion blockers, and defense-in-depth route authorization boundaries across all 31 application modules.

---

### 1. Executive Summary & Audit Findings

Following comprehensive audit directives, an in-depth, end-to-end audit was conducted across all 31 modules, components, and controllers covering create, read, update, and delete (CRUD) operations, authentication, and authorization.

#### Key Architectural Strengths Confirmed:
1. **Transactional Immutability (Axioms 5, 6):**
   - Financial journals, ledger facts, sales, purchase orders, goods receipt notes, invoices, customer agreements, running charts, and expenses prohibit destructive hard-deletion, enforcing append-only corrections and audit trails.
2. **Concurrency & Optimistic Locking (Axiom 6):**
   - Read/write mutations consistently enforce `row_version` checks preventing mid-air collisions.
3. **Primary Key Concealment (Axiom 10):**
   - Client APIs and interfaces utilize natural, readable business identifiers (`customer_number`, `supplier_number`, `item_code`, `sale_number`, etc.) without exposing internal database primary keys.

#### Identified Integrity and Authorization Vulnerabilities:
1. **Master Data Referential Integrity Blocker Gaps:**
   - Master data entities (`Customer`, `Supplier`, `Vehicle`, `Item`, `Warehouse`, `WarehouseLocation`, `CustomerCategory`, `SupplierCategory`) previously verified references in legacy tables but lacked blocker assertions for newer domain tables:
     - Customers referenced in `sales` (Fast Sales), `vehicle_rental_customer_agreements`, `vehicle_rental_reservations`, `vehicle_rental_expense_allocations`, or `promotion_cards` could be deleted, causing orphaned records.
     - Suppliers referenced in `vehicle_rental_owner_agreements`, `vehicle_rental_finance_agreements`, or `vehicle_rental_expenses` could be deleted.
     - Customer and Supplier parent categories could be deleted while child categories existed, orphaning hierarchy branches.
     - Vehicles could be deleted without prior checks against operational tables (`vehicle_service_jobs`, `vehicle_rental_owner_agreements`, `vehicle_rental_uses`, `vehicle_rental_running_charts`, `vehicle_rental_finance_agreements`, `vehicle_rental_expenses`, `vehicle_rental_reservations`, `promotion_cards`, and `vehicle_ownerships`).
     - Items lacked reference blocker checks against `sale_lines` and `promotion_card_benefits`.
     - Warehouses and Warehouse Locations lacked reference blocker checks against `sales`.
2. **Defense-in-Depth Route Authorization Gaps:**
   - While controller actions asserted fine-grained authorization, several non-parameterized route endpoints lacked route-level `tenant.permission` middleware, permitting unauthorized callers to invoke controller bootstrap logic:
     - `Audit/Routes/api.php`: `api/v1/audit-logs` lacked `AuditPermission::LOGS_VIEW`.
     - `Tenant/Routes/api.php`: profile, domains, and documents endpoints lacked route-level permissions.
     - `Voucher/Routes/api.php`: payment voucher routes lacked multi-permission route middleware.
     - `User/Routes/api.php`: user roles sync, permissions sync, organization access, status updates, device management, and documents lacked explicit route middleware.
     - `VehicleRental/Routes/api.php`: dashboard, reservations, driver employees, finance agreements, expenses, and reports lacked route-level permission middleware.

---

### 2. Implemented Code Remediations

#### Master Data Referential Blockers:
1. **`app/Modules/Customer/Services/CustomerBlockerService.php`**:
   - Added deletion blocker checks for `sales`, `vehicle_rental_customer_agreements`, `vehicle_rental_reservations`, `vehicle_rental_expense_allocations`, and `promotion_cards` with guarded `Schema::hasTable` checks, throwing `ConflictHttpException`.
2. **`app/Modules/Customer/Services/CustomerCategoryService.php`**:
   - Added `$category->children()->exists()` check throwing `InvalidArgumentException` before parent category deletion.
3. **`app/Modules/Supplier/Services/SupplierBlockerService.php`**:
   - Added deletion blocker checks for `vehicle_rental_owner_agreements`, `vehicle_rental_finance_agreements`, and `vehicle_rental_expenses`, throwing `ConflictHttpException`.
4. **`app/Modules/Supplier/Services/SupplierCategoryService.php`**:
   - Added `$category->children()->exists()` check throwing `InvalidArgumentException` before parent category deletion.
5. **`app/Modules/Vehicle/Services/VehicleQueryService.php`**:
   - Implemented `assertUnused(Vehicle $vehicle)` prior to vehicle deletion, checking `vehicle_service_jobs`, `vehicle_rental_owner_agreements`, `vehicle_rental_uses`, `vehicle_rental_running_charts`, `vehicle_rental_finance_agreements`, `vehicle_rental_expenses`, `vehicle_rental_reservations`, `promotion_cards`, and `vehicle_ownerships`, throwing `ConflictHttpException`.
6. **`app/Modules/Item/Services/ItemQueryService.php`**:
   - Added `'sale_lines' => ['item_id']` and `'promotion_card_benefits' => ['item_id']` to `referenceColumns()`, throwing `InvalidArgumentException` on attempt to delete referenced items.
7. **`app/Modules/Warehouse/Services/WarehouseDomainService.php`**:
   - Added `'sales' => ['warehouse_id']` to `warehouseReferenceColumns()` and `'sales' => ['warehouse_location_id']` to `locationReferenceColumns()`, throwing `InvalidArgumentException` on attempt to delete referenced warehouses or locations.

#### Route-Level Authorization Hardening:
1. **`app/Modules/Audit/Routes/api.php`**:
   - Attached `'tenant.permission:'.AuditPermission::LOGS_VIEW` to `api/v1/audit-logs`.
2. **`app/Modules/Tenant/Routes/api.php`**:
   - Attached `'tenant.permission'` to tenant profile (`TENANTS_VIEW`/`UPDATE`), domains (`DOMAINS_VIEW`/`MANAGE`), and documents (`DOCUMENTS_VIEW`/`MANAGE`) endpoints.
3. **`app/Modules/Voucher/Routes/api.php`**:
   - Attached variadic multi-permission `'tenant.permission:'.PaymentPermission::PAYMENTS_VIEW.','.FinancePermission::JOURNALS_VIEW` across payment voucher endpoints.
4. **`app/Modules/User/Routes/api.php`**:
   - Attached route middleware to user management endpoints: `users.index`, `users.store`, `users.password-policy`, `users.status`, `users.roles.sync`, `users.permissions.sync`, `users.organization-access.sync`, `users.destroy`, `users.documents.*`, `users.devices.*`.
5. **`app/Modules/VehicleRental/Routes/api.php`**:
   - Attached route middleware to `dashboard`, `driver-employees`, `reservations`, `finance-agreements`, `expenses`, and `reports` endpoints.

---

### 3. Verification & Automated Test Coverage

1. **Master Data Referential Blocker Feature Tests (14 passed, 35 assertions):**
   - `tests/Feature/Customer/CustomerBlockerTest.php`: Customer blocked by sales and rental agreements; parent category blocked by child category. (3 passed, 10 assertions)
   - `tests/Feature/Supplier/SupplierBlockerTest.php`: Supplier blocked by owner agreements and rental expenses; parent category blocked by child category. (3 passed, 10 assertions)
   - `tests/Feature/Vehicle/VehicleBlockerTest.php`: Vehicle blocked by rental uses; unreferenced vehicle successfully soft-deleted. (2 passed, 5 assertions)
   - `tests/Feature/Item/ItemBlockerTest.php`: Item blocked by sale lines and promotion card benefits; unreferenced item successfully soft-deleted. (3 passed, 5 assertions)
   - `tests/Feature/Warehouse/WarehouseBlockerTest.php`: Warehouse and location blocked by sales; unreferenced warehouse successfully soft-deleted. (3 passed, 5 assertions)

2. **Route Compilation:**
   - `php artisan route:list`: 1,052 routes compiled cleanly with zero errors.

3. **Full Backend Test Suite:**
   - `php artisan test`: 1,325 passed (13,797 assertions) in 202.26s.

4. **Frontend TypeScript Typecheck:**
   - `npm run typecheck`: 0 diagnostics, clean exit.

5. **Frontend Unit & Integration Test Suite:**
   - `npm test -- --run`: 174 test files passed, 726 tests passed (0 failures) in 325.96s.
