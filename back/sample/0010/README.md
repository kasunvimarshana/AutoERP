# KV Inventory Management System

**Laravel 12 · PHP 8.2 · DDD + Clean Architecture · Multi-tenant · Fully Auditable**

Aligned to [KVAutoERP](https://github.com/kasunvimarshana/KVAutoERP) architecture.
All patterns confirmed from PR #37 diffs, `composer.json`, and `.env.example`.

---

## Architecture

```
app/Modules/{Name}/
├── Domain/                     ← Pure PHP. Zero framework deps.
│   ├── Entities/               ← Business objects (Product, Batch, Warehouse…)
│   ├── ValueObjects/           ← Immutable, self-validating (ProductType, UnitOfMeasure…)
│   ├── Events/                 ← Domain events (ProductCreated, BatchExpired…)
│   ├── Exceptions/             ← Domain exceptions (ProductNotFoundException…)
│   └── RepositoryInterfaces/   ← Contracts only — no implementation
├── Application/                ← Use cases. No HTTP. No Eloquent.
│   ├── DTOs/                   ← Self-validating (extends BaseDto → rules() method)
│   ├── ServiceInterfaces/      ← Contracts for each use case
│   └── Services/               ← Implement interface, extend BaseService
└── Infrastructure/             ← Framework-aware (Laravel, Eloquent, HTTP)
    ├── Http/
    │   ├── Controllers/        ← Thin adapters, no business logic
    │   ├── Requests/           ← Laravel FormRequest validation
    │   └── Resources/          ← API output transformation
    └── Persistence/Eloquent/
        ├── Models/             ← Eloquent model (separate from domain entity)
        └── Repositories/       ← Implements domain interface, maps entity ↔ model
```

---

## Confirmed Patterns (from KVAutoERP PR #37)

| Pattern | Evidence |
|---------|---------|
| `declare(strict_types=1)` on all files | All new files in PR #37 |
| `BaseDto::fromArray()` factory with `rules()` validation | `ProductData` diff |
| `BaseService::execute()` wraps `handle()` in DB transaction | Import in `CreateProductService` |
| Repository interface in Domain, Eloquent impl in Infrastructure | PR #37 imports |
| Domain entity is pure PHP — no Eloquent | `Product` entity in diff |
| `UnitOfMeasure[]` typed array on domain entity | PR #37 `Product` entity diff |
| `null` UoM = preserve; `[]` UoM = clear | `UpdateProductService` diff |
| Domain events dispatched from Application layer | `ProductCreated`, `ProductUpdated` imports |
| Per-module migrations under `app/Modules/{Name}/database/migrations/` | PR #37 file tree |
| `tenant_id` on all domain data | `ProductData` rules: `exists:tenants,id` |
| `Modules\Core` shared kernel | Imported in all module files |

---

## User-Configurable Methods

All configurable per-tenant, with per-org/per-warehouse/per-product override hierarchy.

### Inventory Valuation Methods
| Method | Description |
|--------|-------------|
| `FIFO` | Oldest costing layers consumed first |
| `LIFO` | Newest layers consumed first |
| `AVCO` | Weighted average recalculated on every receipt |
| `FEFO` | Nearest expiry date consumed first |
| `FMFO` | Oldest manufacture date consumed first |
| `specific_id` | Each lot/batch/serial tracked at its own cost |
| `standard_cost` | Fixed preset cost; purchase-price variances captured |
| `retail` | Cost estimated via cost-to-retail ratio |

### Stock Rotation Strategies
| Strategy | Use case |
|----------|---------|
| `FIFO` | General retail, shelf-stable |
| `LIFO` | Bulk liquids, coal, sand |
| `FEFO` | Food, pharma, cosmetics |
| `FMFO` | Perishables with manufacture date |
| `LEFO` | Shortest remaining shelf life first |

### Allocation Algorithms
| Algorithm | Description |
|-----------|-------------|
| `strict_reservation` | Hard lock to specific lot + location |
| `soft_reservation` | Claim quantity pool with TTL expiry |
| `fair_share` | Proportional split of scarce stock |
| `priority_based` | Highest-priority orders first |
| `wave_picking` | Group by shipping deadline / carrier cutoff |
| `zone_picking` | Assign pickers to warehouse zones |
| `batch_picking` | Single picker, multiple orders, one pass |
| `cluster_picking` | Cart tote picking for multiple orders |

---

## Modules

| Module | Domain Entities | Application Services | Notes |
|--------|----------------|---------------------|-------|
| `Core` | — | `BaseService`, `BaseDto` | Shared kernel: `Money`, `Sku`, `TenantId`, `Quantity` |
| `Audit` | `AuditLog` | `AuditService` | Immutable; `Auditable` trait for all models |
| `Product` | `Product` | Create, Update, Delete, Get, List | All types: physical/service/digital/subscription/combo/variable/wip/kit |
| `Batch` | `Batch`, `Lot`, `SerialNumber` | QC workflow, genealogy | Full forward/backward traceability |
| `Warehouse` | `Warehouse`, `Zone`, `Location` | CRUD + stock view | Per-warehouse method overrides |
| `Inventory` | `StockPosition`, `CostingLayer` | `InventoryEngine` | All 8 valuation methods, all 5 rotation strategies |
| `Allocation` | `StockAllocation`, `PickList` | `AllocationService` | All 8 allocation algorithms |
| `Procurement` | `PurchaseOrder`, `GoodsReceipt` | Full PO lifecycle | GRN with QC, landed costs |
| `Sales` | `SalesOrder`, `Shipment`, `RMA` | Confirm, allocate, ship, return | Multi-channel, bundle expansion |
| `Production` | `ProductionOrder`, `BillOfMaterials` | BOM-driven manufacturing | Material issue, produce, scrap |
| `Reporting` | — | 11 report types | COGS, turnover, ageing, margin, ABC, batch trace |

---

## Database: 47 Tables Across 9 Migrations

```
0001  tenants, organizations
0002  audit_logs
0003  inventory_settings, product_valuation_overrides
0004  warehouses, warehouse_zones, storage_locations
0005  units_of_measure, tax_classes, product_categories,
      attribute_groups, attributes, products, product_variants,
      variant_attribute_values, product_components,
      price_lists, price_list_items
0006  batches, lots, serial_numbers, batch_genealogy, batch_documents
0007  stock_positions, stock_ledger_entries (IMMUTABLE),
      costing_layers, costing_layer_consumptions,
      cost_variances, document_sequences
0008  suppliers, supplier_products, purchase_orders,
      purchase_order_lines, goods_receipts, goods_receipt_lines,
      landed_costs, landed_cost_allocations
0009  customers, sales_orders, sales_order_lines,
      stock_allocations, pick_lists, pick_list_lines,
      shipments, shipment_items, return_authorizations,
      return_authorization_lines, stock_transfers,
      stock_transfer_lines, stock_adjustments,
      stock_adjustment_lines, physical_counts,
      physical_count_items, bills_of_materials,
      bom_components, production_orders, production_order_lines,
      reorder_rules, stock_alerts, inventory_snapshots,
      inventory_snapshot_lines, product_classifications
```

---

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan passport:install

# Register in config/app.php providers:
# App\Providers\InventoryServiceProvider::class
```

## Development

```bash
composer dev     # Runs server + queue + log tail + Vite concurrently
composer test    # Full test suite
composer test:unit  # Domain unit tests only (no database needed)
composer lint    # Laravel Pint code style fixer
```

## Testing

```bash
php artisan test --testsuite=Unit
# 70+ tests covering all value objects, domain entities, and business rules
# Pure PHP — runs without database or Laravel bootstrap
```
