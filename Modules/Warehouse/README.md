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

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
