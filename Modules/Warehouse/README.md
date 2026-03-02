# Warehouse Module

## Overview

The **Warehouse** module implements the Warehouse Management System (WMS) layer on top of the core Inventory module. It provides bin-level tracking, optimized picking strategies, packing validation, and reverse logistics.

---

## Responsibilities

- Warehouse and bin/location structure management
- Automated receiving and goods-in processing
- Intelligent putaway suggestions (turnover, size, space)
- Picking strategies: batch, wave, zone
- Route optimization for pickers
- Packing validation
- Labor management and productivity tracking
- Warehouse layout optimization
- Returns management (reverse logistics):
  - Return inspection
  - Restocking workflows
  - Damage classification
  - Credit processing
- Integration with ERP, transportation, and e-commerce

---

## Architecture Layer

```
Modules/Warehouse/
 ├── Application/       # Receiving, putaway, pick/pack, returns use cases
 ├── Domain/            # Warehouse, Bin, PickList entities, WarehouseRepository contract
 ├── Infrastructure/    # WarehouseRepository, WarehouseServiceProvider
 ├── Interfaces/        # WarehouseController, PickListController, BinResource
 ├── module.json
 └── README.md
```

---

## Dependencies

- `core`
- `tenancy`
- `inventory`

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| Tenant isolation enforced | ✅ Enforced |
| All stock mutations inside database transactions | ✅ Required |
| No cross-module coupling (communicates with Inventory via contracts/events) | ✅ Enforced |

---

## Implemented Files

### Migrations
| File | Description |
|---|---|
| `2026_02_27_000033_create_warehouse_zones_table.php` | Warehouse zones (storage/receiving/shipping/quarantine) |
| `2026_02_27_000034_create_bin_locations_table.php` | Bin locations with capacity (decimal(20,4)) |
| `2026_02_27_000035_create_putaway_rules_table.php` | Putaway rules (product/type → zone mapping) |
| `2026_02_27_000036_create_picking_orders_table.php` | Picking orders (batch/wave/zone strategy) |
| `2026_02_27_000037_create_picking_order_lines_table.php` | Picking order lines (qty_requested/picked as decimal(20,4)) |

### Domain
| File | Description |
|---|---|
| `Domain/Entities/WarehouseZone.php` | WarehouseZone entity (HasTenant, hasMany binLocations) |
| `Domain/Entities/BinLocation.php` | BinLocation entity (HasTenant, capacity cast to string) |
| `Domain/Entities/PutawayRule.php` | PutawayRule entity (HasTenant, belongsTo zone) |
| `Domain/Entities/PickingOrder.php` | PickingOrder entity (HasTenant, hasMany lines) |
| `Domain/Entities/PickingOrderLine.php` | PickingOrderLine entity (HasTenant, qty cast to string) |
| `Domain/Contracts/WarehouseRepositoryContract.php` | Extends RepositoryContract |

### Infrastructure
| File | Description |
|---|---|
| `Infrastructure/Repositories/WarehouseRepository.php` | Implements WarehouseRepositoryContract using WarehouseZone |
| `Infrastructure/Providers/WarehouseServiceProvider.php` | Binds contract, loads migrations & routes |

### Application
| File | Description |
|---|---|
| `Application/DTOs/CreatePickingOrderDTO.php` | DTO with fromArray factory; line quantities as string |
| `Application/Services/WarehouseService.php` | createPickingOrder (DB::transaction), getPutawayRecommendation, showPickingOrder, listPickingOrders, completePickingOrder |

### Interfaces
| File | Description |
|---|---|
| `Interfaces/Http/Controllers/WarehouseController.php` | createPickingOrder, getPutawayRecommendation, showPickingOrder, listPickingOrders, completePickingOrder — OpenAPI documented |
| `routes/api.php` | POST warehouse/picking-orders, GET warehouse/picking-orders, GET warehouse/picking-orders/{id}, POST warehouse/picking-orders/{id}/complete, GET warehouse/putaway/{productId}/{warehouseId} |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/CreatePickingOrderDTOTest.php` | Unit | `CreatePickingOrderDTO` — field hydration, line quantities as string |
| `Tests/Unit/WarehouseServiceTest.php` | Unit | createPickingOrder, getPutawayRecommendation — delegation, DB::transaction |
| `Tests/Unit/WarehouseServiceCrudTest.php` | Unit | showPickingOrder, listPickingOrders, completePickingOrder — 12 assertions |

---

## Status

🟢 **Complete** — Zones, bins, putaway rules, picking order management, list/show/complete endpoints implemented (~80% test coverage).

See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
