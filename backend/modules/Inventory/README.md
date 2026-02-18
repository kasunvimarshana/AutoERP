# Inventory Management Module

## Overview

The Inventory Management Module provides comprehensive functionality for managing products, stock, warehouses, and inventory operations in the AutoERP system. It supports multi-location, multi-warehouse operations with advanced features like batch tracking, serial number management, and expiry date handling.

## Features

### Product Management
- **Product Types**: Inventory products, services, bundle products, composite products
- **SKU Management**: Unique SKU generation and management
- **Variant Modeling**: Product variants with dynamic attributes (size, color, etc.)
- **Dynamic Attributes**: Custom attributes per product category
- **Categories & Classification**: Hierarchical product categorization

### Stock Management
- **Append-Only Stock Ledger**: Immutable transaction history
- **Real-Time Stock Tracking**: Current stock levels per location
- **Batch & Lot Tracking**: Batch number and lot tracking
- **Serial Number Management**: Individual item serialization
- **Expiry Date Tracking**: FIFO/FEFO inventory management
- **Multi-Location Support**: Stock across multiple warehouses and locations

### Warehouse Operations
- **Warehouse Management**: Multiple warehouses per tenant
- **Location Hierarchy**: Zones, aisles, racks, bins
- **Stock Transfers**: Inter-warehouse and inter-location transfers
- **Stock Adjustments**: Adjustment reasons and approval workflows
- **Cycle Counting**: Periodic inventory verification
- **Goods Receipt**: Receiving stock from purchase orders

### Inventory Operations
- **Stock Movements**: Track all stock movements
- **Reservations**: Reserve stock for orders
- **Allocations**: Allocate stock to specific orders
- **Returns**: Handle stock returns (customer & supplier)
- **Damaged Stock**: Track and manage damaged inventory
- **Stock Reordering**: Automatic reorder point alerts

### Pricing & Costing
- **Multi-Unit Pricing**: Different prices for different UOMs
- **Tiered Pricing**: Volume-based pricing
- **Conditional Pricing**: Customer-specific, date-range, quantity-based
- **Seasonal Pricing**: Time-based pricing rules
- **Cost Methods**: FIFO, LIFO, WAC (Weighted Average Cost)
- **Margin Calculation**: Profit margin tracking

### Advanced Features
- **Multi-UOM Support**: Base unit and conversion factors
- **Kitting**: Bundle multiple products
- **BOM Integration**: Bill of materials for manufacturing
- **Barcode Management**: Generate and scan barcodes
- **QR Code Support**: QR code generation for products
- **Image Management**: Multiple product images

## Architecture

### Models

#### Core Models
- **Product**: Main product entity
- **ProductVariant**: Product variations
- **ProductAttribute**: Dynamic product attributes
- **Category**: Product categories (hierarchical)

#### Stock Models
- **StockLedger**: Append-only transaction log
- **StockLevel**: Current stock per location (materialized view)
- **BatchNumber**: Batch/lot information
- **SerialNumber**: Serial number tracking

#### Warehouse Models
- **Warehouse**: Warehouse/facility entity
- **Location**: Storage locations within warehouses
- **StockTransfer**: Inter-location transfers
- **StockAdjustment**: Stock adjustment records

#### Pricing Models
- **PricingRule**: Flexible pricing rules
- **ProductPrice**: Product pricing records
- **DiscountRule**: Discount configurations

### Services

- **ProductService**: Product CRUD and business logic
- **StockService**: Stock management operations
- **WarehouseService**: Warehouse operations
- **PricingService**: Price calculation and management
- **TransferService**: Stock transfer workflows
- **AdjustmentService**: Stock adjustment handling

### Repositories

- **ProductRepository**: Product data access
- **StockLedgerRepository**: Stock ledger queries
- **WarehouseRepository**: Warehouse data access
- **PricingRepository**: Pricing data access

## API Endpoints

### Products
```
GET    /api/inventory/products                    # List products
POST   /api/inventory/products                    # Create product
GET    /api/inventory/products/{id}               # Get product details
PUT    /api/inventory/products/{id}               # Update product
DELETE /api/inventory/products/{id}               # Delete product
GET    /api/inventory/products/{id}/variants      # Get product variants
POST   /api/inventory/products/{id}/variants      # Add variant
GET    /api/inventory/products/{id}/stock         # Get stock levels
POST   /api/inventory/products/bulk-import        # Bulk import products
```

### Stock Management
```
GET    /api/inventory/stock-ledger                # Query stock ledger
POST   /api/inventory/stock/adjust                # Adjust stock
POST   /api/inventory/stock/transfer              # Transfer stock
GET    /api/inventory/stock/levels                # Current stock levels
POST   /api/inventory/stock/reserve               # Reserve stock
POST   /api/inventory/stock/receive               # Goods receipt
```

### Warehouses
```
GET    /api/inventory/warehouses                  # List warehouses
POST   /api/inventory/warehouses                  # Create warehouse
GET    /api/inventory/warehouses/{id}             # Get warehouse
PUT    /api/inventory/warehouses/{id}             # Update warehouse
GET    /api/inventory/warehouses/{id}/locations   # Get locations
GET    /api/inventory/warehouses/{id}/stock       # Get warehouse stock
```

### Pricing
```
GET    /api/inventory/products/{id}/pricing       # Get product pricing
POST   /api/inventory/products/{id}/pricing       # Set pricing rule
GET    /api/inventory/pricing/calculate           # Calculate price
POST   /api/inventory/pricing/rules               # Create pricing rule
```

## Database Schema

### Products
```sql
products (
    id, tenant_id, sku, name, description, product_type,
    category_id, base_uom_id, track_inventory, track_batches,
    track_serials, has_expiry, reorder_level, reorder_quantity,
    cost_method, status, created_at, updated_at, deleted_at
)

product_variants (
    id, tenant_id, product_id, sku, variant_attributes,
    price_adjustment, cost_adjustment, barcode, status
)

product_attributes (
    id, tenant_id, product_id, attribute_name, attribute_value,
    attribute_type
)

categories (
    id, tenant_id, parent_id, name, code, description, sort_order
)
```

### Stock
```sql
stock_ledger (
    id, tenant_id, product_id, variant_id, warehouse_id, location_id,
    transaction_type, quantity, uom_id, batch_number, serial_number,
    reference_type, reference_id, unit_cost, total_cost, notes,
    transaction_date, created_by, created_at
)

stock_levels (
    id, tenant_id, product_id, variant_id, warehouse_id, location_id,
    quantity_available, quantity_reserved, quantity_allocated,
    quantity_damaged, last_movement_at, updated_at
)

batch_numbers (
    id, tenant_id, product_id, batch_number, manufacturing_date,
    expiry_date, quantity_manufactured, quantity_remaining
)

serial_numbers (
    id, tenant_id, product_id, serial_number, warehouse_id,
    location_id, status, purchase_date, warranty_expiry
)
```

### Warehouses
```sql
warehouses (
    id, tenant_id, code, name, address, city, state, country,
    postal_code, phone, email, manager_id, is_active
)

locations (
    id, tenant_id, warehouse_id, parent_id, code, name, location_type,
    capacity, is_active
)

stock_transfers (
    id, tenant_id, transfer_number, from_warehouse_id, to_warehouse_id,
    status, requested_by, approved_by, completed_by, transfer_date,
    notes
)

stock_adjustments (
    id, tenant_id, adjustment_number, warehouse_id, adjustment_type,
    reason, status, requested_by, approved_by, adjustment_date
)
```

## Events

- `ProductCreated`: Fired when a new product is created
- `ProductUpdated`: Fired when product details change
- `StockAdjusted`: Fired when stock is adjusted
- `StockTransferred`: Fired when stock is transferred
- `LowStockAlert`: Fired when stock falls below reorder level
- `BatchExpiring`: Fired when batch approaching expiry
- `StockReserved`: Fired when stock is reserved
- `StockAllocated`: Fired when stock is allocated

## Permissions

```php
'inventory.products.view'
'inventory.products.create'
'inventory.products.update'
'inventory.products.delete'
'inventory.stock.view'
'inventory.stock.adjust'
'inventory.stock.transfer'
'inventory.warehouses.view'
'inventory.warehouses.create'
'inventory.warehouses.update'
'inventory.pricing.view'
'inventory.pricing.manage'
```

## Integration Points

### With Other Modules

- **Procurement**: Stock receipts from purchase orders
- **Sales**: Stock reservations for sales orders
- **Manufacturing**: Raw material consumption, finished goods production
- **Accounting**: Inventory valuation, COGS calculation
- **Core**: Audit logging, tenant isolation
- **IAM**: Permission-based access control

## Usage Examples

### Create Product
```php
$product = app(ProductService::class)->create([
    'sku' => 'PROD-001',
    'name' => 'Widget A',
    'product_type' => ProductType::INVENTORY,
    'category_id' => 1,
    'base_uom_id' => 1,
    'track_inventory' => true,
    'track_batches' => true,
    'cost_method' => CostMethod::FIFO,
]);
```

### Adjust Stock
```php
$adjustment = app(StockService::class)->adjust([
    'product_id' => 1,
    'warehouse_id' => 1,
    'quantity' => 100,
    'transaction_type' => TransactionType::ADJUSTMENT_IN,
    'reason' => 'Initial stock',
    'unit_cost' => 10.00,
]);
```

### Transfer Stock
```php
$transfer = app(TransferService::class)->create([
    'product_id' => 1,
    'from_warehouse_id' => 1,
    'to_warehouse_id' => 2,
    'quantity' => 50,
    'notes' => 'Warehouse replenishment',
]);
```

### Calculate Price
```php
$price = app(PricingService::class)->calculatePrice([
    'product_id' => 1,
    'quantity' => 10,
    'customer_id' => 5,
    'date' => now(),
]);
```

## Configuration

Module configuration in `config/inventory.php`:

```php
return [
    'default_cost_method' => 'fifo',
    'negative_stock_allowed' => false,
    'auto_create_sku' => true,
    'sku_prefix' => 'PRD',
    'enable_batch_tracking' => true,
    'enable_serial_tracking' => true,
    'expiry_alert_days' => 30,
    'low_stock_alert_percentage' => 20,
    'barcode_format' => 'code128',
];
```

## Testing

```bash
# Run inventory module tests
php artisan test --filter=Inventory

# Run specific test
php artisan test modules/Inventory/tests/Feature/ProductManagementTest.php
```

## Future Enhancements

- [ ] RFID tag integration
- [ ] AI-powered demand forecasting
- [ ] Automated reordering
- [ ] IoT sensor integration for real-time tracking
- [ ] Blockchain for supply chain traceability
- [ ] Mobile app for warehouse operations
- [ ] Computer vision for quality inspection
- [ ] Drone inventory counting
