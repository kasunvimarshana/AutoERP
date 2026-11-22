# Inventory Module Audit

Date: 2026-06-14

Scope:

- `app/Modules/Inventory`
- Inventory-facing code in `app/Modules/Item`, `app/Modules/UOM`, and `app/Modules/Warehouse`
- Purchase, Sales, and Vehicle Service inventory integrations
- Inventory migrations, models, services, DTOs, requests, resources, routes, tests, and frontend components

Baseline verification:

- `php artisan test app/Modules/Inventory/Tests/InventoryEngineTest.php`: 23 tests, 121 assertions passed
- `npm run build`: passed

## Confirmed Findings

### Critical integrity and concurrency

1. `inventory_stock_balances` uses a composite unique key containing nullable columns. MySQL permits multiple rows when any indexed dimension is `NULL`, so the constraint does not guarantee one balance per inventory dimension.
2. `StockBalanceService::getOrCreateForUpdate()` serializes normal posting through an item lock, but `getOrCreate()` does not. Stock-count creation uses the unlocked path.
3. `InventoryNumberService` generates numbers with `count() + 1`. Concurrent transactions can generate the same movement, reservation, allocation, transfer, adjustment, cost-adjustment, or stock-count number.
4. Stock counts capture a balance without locking it and can later post a variance based on a stale system quantity. There is no reconciliation check that stock remained unchanged between count creation and posting.
5. Allocation creation has no source-level duplicate protection. Concurrent or repeated requests for the same source line can create more than one allocation when stock is sufficient.
6. Stock adjustments retain a system-quantity snapshot but do not revalidate it while posting, so intervening movements can make the adjustment stale.

### UOM clarity and traceability

1. Operational calculations are converted to the item base UOM, but entered quantity, entered UOM, and conversion factor are discarded.
2. Existing columns named `quantity`, `quantity_reserved`, `quantity_allocated`, and stock-balance quantity columns actually hold canonical base quantities. Resources and frontend types do not make that contract explicit.
3. Purchase, Sales, and Vehicle Service integrations pre-convert quantities outside Inventory before calling `InventoryFacade`. This duplicates the Inventory conversion responsibility and prevents Inventory from auditing the entered basis.
4. Sales-return cost restoration derives a base-unit cost from allocation issues and then passes it through an entered-UOM conversion as if it were an entered-unit cost. Non-base UOM returns can therefore understate or overstate receipt value.
5. Stock balances do not store their current base UOM. Item base-UOM conversion mutates balance quantities and costs correctly, but the balance record itself cannot state its quantity basis.
6. Serials are intentionally maintained as an identity/status ledger, not as a stock-balance dimension. A balance aggregates serial-tracked units by item, variant, warehouse, location, and batch; allocation lines bind individual serials to that balance. Adding `serial_number_id` to balances would change current behavior and is not recommended in this cleanup.

### Audit-history foreign keys

1. Stock balances and valuation layers cascade on item and warehouse deletion.
2. Adjustment, transfer, allocation, cost-adjustment, and stock-count lines cascade when their parent is force-deleted.
3. Allocation issues cascade with allocations and allocation lines.
4. Several historical UOM, location, batch, serial, and variant references use `nullOnDelete`, which can remove historical identity from audit rows.
5. Soft deletes reduce the immediate exposure, but force deletes or maintenance scripts can still erase or detach inventory history.

### Stock-state consistency

1. Available quantity is correctly recalculated as on-hand less reserved, allocated, returned, damaged, quarantine, expired, and scrapped quantities.
2. Reservation, allocation, issue, release, and transfer flows update balances inside transactions and record state changes.
3. There is no reusable reconciliation assertion for non-negative state buckets and the available-quantity equation.
4. Damaged, quarantine, returned, expired, and scrapped columns are readable and included in availability, but no Inventory service currently owns transitions into or out of those buckets. Direct model mutation can bypass state-change audit.

### Valuation

1. FIFO, weighted average, standard cost, and manual cost are isolated behind `ValuationMethodInterface`.
2. FIFO consumption and reversal retain layer-level audit through `inventory_valuation_consumptions`.
3. Weighted-average pool creation relies on the item/balance lock taken by movement posting. Direct strategy use would not independently prevent duplicate pools.
4. Weighted-average receipt reversal is pool-based rather than receipt-provenance-based. It can be difficult to prove exact historical attribution after later receipts and issues. This requires a separate business decision and should not be silently changed.
5. `ValuationLayerData` is unused.

### Allocation

1. FIFO, FEFO, batch, serial, and manual strategies are isolated behind `AllocationStrategyInterface`.
2. Balance rows are locked while allocation plans are created.
3. Serial selection and serial status updates are locked.
4. `InventoryAllocationService` mixes creation, preview, issue, release, reallocation, strategy resolution, reservation conversion, serial transitions, and audit recording in one large class.
5. Duplicate lines in stock counts, adjustments, transfers, and cost adjustments are not rejected, creating ambiguous reconciliation and audit mapping.

### Services and controllers

1. `StockMovementService` mixes draft recording, posting, valuation, balance mutation, serial mutation, reversal, UOM-history conversion, and allocation reversal bookkeeping.
2. `InventoryAllocationService` and `StockTransferService` contain multiple workflow responsibilities.
3. `StockAllocationService` and `StockAvailabilityService` are compatibility pass-through wrappers. They are used by existing callers and should remain until callers are migrated.
4. `InventoryController` contains all Inventory HTTP endpoints. Individual methods are mostly thin, but query filtering and action routing are concentrated in a 15 KB controller.
5. Controller filtering calls `Schema::hasColumn()` during requests. The filter sets are already known and can be explicit.

### Requests, DTOs, and resources

1. DTOs are side-effect free and generally predictable.
2. Request validation is duplicated for common inventory dimensions and line fields, but the duplication is still readable; a large validation abstraction is not justified.
3. Resources currently inherit generic model serialization. They do not calculate business values, but they also do not expose explicit base-quantity semantics.
4. `StockPostingResult` is only used by one service method and is not part of current HTTP workflows.

### Frontend

1. `InventoryWorkflowTabs.tsx` contains four independent workflows and is the largest Inventory frontend component.
2. The backend adjustment workflow has no equivalent Inventory page tab or API client methods.
3. Quantity labels do not state that direct Inventory forms use base quantities unless a UOM is supplied by another integration.
4. Frontend decimal aggregation uses scaled integers and does not use binary floating-point arithmetic.
5. Backend remains the source of truth for availability, valuation, posting, and status transitions.

### Tests

1. `InventoryEngineTest.php` is a 44 KB integration test covering unrelated movement, allocation, valuation, transfer, stock-count, tracking, and report concerns.
2. Existing tests cover the main happy paths and many validation cases.
3. Missing focused coverage includes nullable-dimension balance uniqueness, number-generation safety, stale stock counts, entered/base UOM audit fields, source-level duplicate allocation, duplicate workflow lines, and balance reconciliation assertions.
4. SQLite tests cannot prove MySQL row-lock behavior. Database constraints and transaction design must carry the production guarantee; a MySQL integration suite remains desirable.

## Planned Remediation Boundaries

- Preserve `InventoryFacade` as the public orchestration boundary.
- Preserve existing routes and response fields; add explicit quantity-basis fields without removing legacy fields.
- Keep serial identity separate from aggregate stock balances.
- Keep existing valuation and allocation strategy interfaces.
- Add only the schema needed for uniqueness, UOM traceability, numbering safety, and audit preservation.
- Split large services and frontend components only along existing workflow responsibilities.
- Reject stale or ambiguous operations rather than silently reconciling unverifiable data.
