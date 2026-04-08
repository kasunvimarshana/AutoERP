# Laravel Inventory Management System
### Enterprise-Grade · Multi-Business · Fully Auditable

---

## Overview

A complete, production-ready inventory management system built on Laravel with support for any business type — retail, manufacturing, wholesale, e-commerce, pharma, food & beverage, SaaS, and more.

---

## Architecture

```
app/
├── Models/                    # Eloquent models (all use Auditable trait)
├── Services/Inventory/
│   ├── InventoryEngine.php    # Core orchestrator
│   ├── ValuationService.php   # All costing methods
│   ├── AllocationService.php  # Allocation algorithms + rotation
│   └── LedgerService.php      # Append-only ledger writer
├── Traits/
│   └── Auditable.php          # Auto-audit on all models
database/migrations/
├── 000001 audit_logs
├── 000002 organizations + warehouses + zones + locations
├── 000003 product catalog (products, variants, attributes, UOM, pricing)
├── 000004 batches, lots, serial numbers, genealogy, documents
├── 000005 stock ledger, positions, costing layers, variances
├── 000006 suppliers, purchase orders, GRN, landed costs
├── 000007 customers, sales orders, allocations, pick lists, shipments, RMA
├── 000008 transfers, adjustments, physical counts, BOM, production orders
└── 000009 reorder rules, alerts, snapshots, ABC/XYZ, roles, webhooks, API keys
```

---

## Database: 60+ Tables

### Foundation
| Table | Purpose |
|-------|---------|
| `organizations` | Multi-tenant root |
| `inventory_settings` | All method/strategy choices per org |
| `warehouses` | Physical + virtual + 3PL + consignment |
| `warehouse_zones` | Receiving, shipping, quarantine, cold, hazmat |
| `storage_locations` | Bin/shelf/aisle/bay/level |
| `audit_logs` | Immutable audit trail for every model |

### Product Catalog
| Table | Purpose |
|-------|---------|
| `products` | All types: physical/service/digital/subscription/bundle/combo/raw material/WIP |
| `product_variants` | Variable products (size × color × material etc.) |
| `attribute_groups` | Size, Color, Material… |
| `attributes` | Red, XL, Cotton… |
| `variant_attribute_values` | Pivot: variant → attribute values |
| `product_components` | Bundle/combo/kit composition |
| `product_uom_conversions` | Unit conversions per product |
| `units_of_measure` | kg, L, pcs, box… with base conversion factors |
| `categories` | Hierarchical (materialized path) |
| `brands` | Brand management |
| `tax_classes` | Tax rates |
| `price_lists` | Multiple pricing tiers |
| `price_list_items` | Per-product/variant pricing with date ranges |

### Batch & Lot Tracking
| Table | Purpose |
|-------|---------|
| `batches` | Manufacturing batch with QC, dates, origin, costs |
| `lots` | Location-level groupings; quantity buckets |
| `serial_numbers` | Per-unit tracking with full lifecycle status |
| `batch_genealogy` | Full traceability tree (split/merge/transform) |
| `batch_documents` | CoA, MSDS, inspection reports |

### Inventory Ledger & Costing
| Table | Purpose |
|-------|---------|
| `stock_positions` | Real-time quantity snapshot per product/warehouse/lot |
| `stock_ledger_entries` | **Immutable** append-only double-entry journal |
| `costing_layers` | FIFO/LIFO/FEFO layers created on every receipt |
| `costing_layer_consumptions` | Trail of how each layer was consumed |
| `cost_variances` | Standard cost purchase-price / usage variances |

### Procurement
| Table | Purpose |
|-------|---------|
| `suppliers` | Vendor/manufacturer/distributor/dropshipper/3PL |
| `supplier_products` | Supplier-specific SKU, cost, lead time, MOQ |
| `purchase_orders` | Full PO lifecycle |
| `purchase_order_lines` | Line-level quantities + receipt tracking |
| `goods_receipts` | GRN with QC workflow |
| `goods_receipt_lines` | Per-line: accepted/rejected quantities + lot assignment |
| `landed_costs` | Freight, duty, insurance |
| `landed_cost_allocations` | Spread landed cost across GRN lines |

### Sales & Fulfillment
| Table | Purpose |
|-------|---------|
| `customers` | Retail/wholesale/B2B with credit limits |
| `sales_orders` | Multi-channel orders |
| `sales_order_lines` | Full quantity lifecycle tracking |
| `stock_allocations` | Soft/hard reservations with algorithm recorded |
| `pick_lists` | Wave/zone/batch/cluster pick lists |
| `pick_list_lines` | Per-item pick instructions with sequence |
| `shipments` | Carrier tracking |
| `shipment_items` | Which lots/batches/serials were shipped |
| `return_authorizations` | RMA with inspection and disposition |
| `return_authorization_lines` | Per-item return with restock/scrap decision |

### Operations
| Table | Purpose |
|-------|---------|
| `stock_transfers` | Inter-warehouse + location-to-location + transit |
| `stock_transfer_lines` | With discrepancy tracking |
| `stock_adjustments` | Write-up/write-down/revaluation/reclassification |
| `stock_adjustment_lines` | System vs. actual quantity |
| `physical_counts` | Full/partial/cycle/spot-check counts |
| `physical_count_items` | Blind count + recount with variance |
| `bills_of_materials` | Manufacturing/assembly/phantom/kit BOMs |
| `bom_components` | Per-component quantity + scrap % |
| `production_orders` | Manufacturing execution |
| `production_order_lines` | Material issue + return tracking |

### Reporting & Config
| Table | Purpose |
|-------|---------|
| `reorder_rules` | min_max/EOQ/days_of_supply per product/warehouse |
| `stock_alerts` | Low stock/expiry/negative stock alerts |
| `inventory_snapshots` | Periodic valuation snapshots |
| `inventory_snapshot_lines` | Per-item snapshot detail |
| `product_classifications` | ABC/XYZ/velocity classification by period |
| `document_sequences` | Auto-numbered document references |
| `roles` / `permissions` | RBAC with warehouse-scope |
| `webhooks` / `webhook_deliveries` | Event-driven integrations |
| `api_keys` | Scoped API access |

---

## User-Configurable Methods

All the following are configurable **per organization**, with **per-product** and **per-warehouse** overrides:

### Inventory Valuation Methods
| Method | Description |
|--------|-------------|
| `FIFO` | First In First Out — oldest cost layers consumed first |
| `LIFO` | Last In First Out — newest cost layers consumed first |
| `AVCO` | Weighted Average Cost — recalculated on every receipt |
| `FEFO` | First Expired First Out — valuation follows expiry sequence |
| `FMFO` | First Manufactured First Out |
| `specific_id` | Specific Identification — each lot/serial has its own cost |
| `standard` | Standard Cost with purchase-price variance capture |
| `retail` | Retail Method — cost estimated via cost-to-retail ratio |

### Stock Rotation Strategies (Physical Picking Order)
| Strategy | Use Case |
|----------|---------|
| `FIFO` | General retail, shelf-stable goods |
| `LIFO` | Bulk liquids, sand, coal |
| `FEFO` | Food, pharmaceuticals, cosmetics |
| `FMFO` | Perishables with manufacture date |
| `LEFO` | Least Expiry First Out — shortest shelf life goes first |

### Allocation Algorithms
| Algorithm | Description |
|-----------|-------------|
| `strict_reservation` | Hard lock to specific lot + location + quantity |
| `soft_reservation` | Claim quantity pool; location resolved at picking |
| `fair_share` | Proportional split of scarce stock across all open orders |
| `priority_based` | VIP / high-priority orders get full allocation first |
| `wave_picking` | Batch orders by shipping deadline or carrier cutoff |
| `zone_picking` | Assign pickers to warehouse zones |
| `batch_picking` | Single picker collects for multiple orders in one pass |
| `cluster_picking` | Cart-based tote picking for multiple orders simultaneously |

### Inventory Management Methods
| Method | Description |
|--------|-------------|
| `perpetual` | Real-time ledger update on every movement |
| `periodic` | Stock calculated at period-end via physical count |

---

## Auditability

Every model in the system uses the `Auditable` trait which automatically:
- Records `created`, `updated`, `deleted`, `restored` events
- Captures `old_values` and `new_values` as JSON
- Stores `user_id`, `ip_address`, `user_agent`, `session_id`, `request_id`
- Is searchable by `auditable_type`, `event`, `user_id`, and `tags`
- The stock ledger is **append-only** — entries are never modified or deleted

---

## Installation

```bash
# Clone and install
composer install

# Copy env
cp .env.example .env
php artisan key:generate

# Configure database in .env, then:
php artisan migrate

# Seed default permissions and roles
php artisan db:seed --class=InventorySeeder

# Register the service provider
# Add to config/app.php providers:
# App\Providers\InventoryServiceProvider::class
```

---

## Service Provider Registration

```php
// app/Providers/InventoryServiceProvider.php
$this->app->singleton(InventoryEngine::class, function ($app) {
    return new InventoryEngine(
        valuationService:  $app->make(ValuationService::class),
        allocationService: $app->make(AllocationService::class),
        rotationService:   new RotationService(),
        ledgerService:     $app->make(LedgerService::class),
    );
});
```

---

## Example Usage

```php
// Receive stock (FIFO valuation, FEFO rotation)
app(InventoryEngine::class)->receiveStock([
    'organization_id' => 1,
    'product_id'      => 42,
    'warehouse_id'    => 1,
    'quantity'        => 100,
    'unit_cost'       => 12.50,
    'movement_type'   => 'purchase_receipt',
    'lot_id'          => 7,
    'batch_id'        => 3,
    'expiry_date'     => '2026-06-01',
    'valuation_method'=> 'FIFO',
    'source_document_type' => 'purchase_order',
    'source_document_id'   => 15,
]);

// Issue stock (AVCO cost, FEFO rotation)
app(InventoryEngine::class)->issueStock([
    'organization_id'   => 1,
    'product_id'        => 42,
    'warehouse_id'      => 1,
    'quantity'          => 30,
    'movement_type'     => 'sales_issue',
    'rotation_strategy' => 'FEFO',
    'source_document_type' => 'sales_order',
    'source_document_id'   => 201,
]);

// Allocate with priority-based algorithm
app(AllocationService::class)->allocate($salesOrder, 'priority_based');
```
