# Inventory Module

## Overview

Ledger-driven inventory management module following Clean Architecture and **Controller → Service → Handler (with Pipeline) → Repository → Entity** pattern.

## Architecture

| Layer | Location | Responsibility |
|---|---|---|
| Domain | `Domain/` | Entities, contracts, enums, value objects |
| Application | `Application/` | Commands, handlers, pipeline pipes |
| Infrastructure | `Infrastructure/` | Eloquent models, repositories, migrations |
| Interfaces | `Interfaces/` | HTTP controllers, requests, resources, routes |

## Key Design Decisions

- **Immutable ledger**: `stock_ledger_entries` is append-only (no updates, no deletes). Historical accuracy is preserved.
- **Balance cache**: `stock_balances` holds a materialised view of current stock per (tenant, warehouse, product). Updated atomically under pessimistic locking inside `DB::transaction()`.
- **BCMath**: All quantity and monetary arithmetic uses BCMath (4 decimal places). Float arithmetic is strictly prohibited.
- **Pessimistic locking**: `lockForUpdate()` on `stock_balances` before every deduction or transfer to prevent race conditions.
- **Pipeline pattern**: `AdjustStockHandler` and `TransferStockHandler` use Laravel Pipeline chaining `ValidateCommandPipe → AuditLogPipe → ValidateStockAvailabilityPipe` before persistence.
- **Tenant isolation**: `WarehouseModel` uses `BelongsToTenant` trait; all repository queries filter by `tenant_id`.

## API Endpoints

| Method | URL | Description |
|---|---|---|
| GET | `/api/v1/warehouses` | List warehouses for tenant |
| POST | `/api/v1/warehouses` | Create warehouse |
| GET | `/api/v1/warehouses/{id}` | Get warehouse by ID |
| POST | `/api/v1/inventory/receive` | Receive stock (goods receipt) |
| POST | `/api/v1/inventory/adjust` | Adjust stock (in or out) |
| POST | `/api/v1/inventory/transfer` | Transfer stock between warehouses |
| GET | `/api/v1/inventory/scan` | Look up product and stock balances by barcode |
| GET | `/api/v1/inventory/stock` | Get current stock balances |
| GET | `/api/v1/inventory/ledger` | Get stock ledger entries |

## Transaction Types

| Type | Direction | Description |
|---|---|---|
| `receipt` | + | Goods received from supplier |
| `shipment` | − | Goods shipped to customer |
| `adjustment_in` | + | Positive stock correction |
| `adjustment_out` | − | Negative stock correction |
| `transfer_in` | + | Received from another warehouse |
| `transfer_out` | − | Sent to another warehouse |
| `return_in` | + | Customer return received |

## Barcode & RFID Scanning

`GET /api/v1/inventory/scan?tenant_id=X&barcode=Y`

Scans a barcode (or RFID tag value stored as barcode) and returns the matching product identity with all current stock balances across warehouses. Uses `ProductRepositoryInterface::findByBarcode()` — a cross-module domain contract with no Eloquent coupling.

- Returns **200** with `{product_id, sku, name, barcode, uom, balances[]}` on success
- Returns **404** when no product matches the barcode within the tenant
- Returns **422** when `barcode` query parameter is omitted

## Concurrency Safety

All stock deduction flows acquire a `FOR UPDATE` lock on the `stock_balances` row before modifying it, within a `DB::transaction()`. This ensures no two concurrent requests can both succeed when insufficient stock exists.

## Tests

See `tests/Feature/Modules/Inventory/InventoryTest.php`.
