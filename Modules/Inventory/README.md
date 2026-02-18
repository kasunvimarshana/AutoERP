# Inventory Management Module

---

**⚠️ IMPLEMENTATION PRINCIPLE**: Rely strictly on native Laravel and Vue features. Always implement functionality manually instead of using third-party libraries.

---


## Overview

The Inventory Management module is a comprehensive, production-ready solution for managing products, warehouses, stock levels, and inventory movements in a multi-tenant Laravel ERP system.

## Architecture

This module follows Clean Architecture and Domain-Driven Design (DDD) principles:

- **Entities**: Rich domain models with business logic
- **Repositories**: Data access abstraction layer
- **Services**: Application business logic
- **Controllers**: HTTP request handling
- **Events**: Domain events for cross-module communication

## Features

### Product Management
- Multi-level product categorization
- SKU and barcode tracking
- Product types: Stockable, Consumable, Service
- Unit of Measure (UoM) with conversions
- Pricing and costing
- Dimensions and weight tracking
- Reorder point management
- Serial and batch tracking support

### Warehouse Management
- Multiple warehouse support
- Warehouse types: Main, Secondary, Transit, Virtual
- Hierarchical stock locations (zones, aisles, racks, shelves, bins)
- Location capacity management
- Geographic coordinates support

### Stock Level Management
- Real-time stock tracking
- Quantity on-hand, reserved, and available
- Stock valuation (FIFO, LIFO, Average)
- Multi-warehouse stock visibility
- Low stock alerts

### Stock Movements
- Receipt (incoming stock)
- Shipment (outgoing stock)
- Inter-warehouse transfers
- Stock adjustments
- Full audit trail
- Reference tracking (links to orders, etc.)

## API Endpoints

### Products
```
GET    /api/v1/products                    - List products
POST   /api/v1/products                    - Create product
GET    /api/v1/products/{id}               - Get product
PUT    /api/v1/products/{id}               - Update product
DELETE /api/v1/products/{id}               - Delete product
GET    /api/v1/products/search?q={query}   - Search products
GET    /api/v1/products/by-category/{id}   - Get products by category
```

### Product Categories
```
GET    /api/v1/product-categories          - List categories
POST   /api/v1/product-categories          - Create category
GET    /api/v1/product-categories/{id}     - Get category
PUT    /api/v1/product-categories/{id}     - Update category
DELETE /api/v1/product-categories/{id}     - Delete category
GET    /api/v1/product-categories/tree     - Get category tree
```

### Warehouses
```
GET    /api/v1/warehouses                  - List warehouses
POST   /api/v1/warehouses                  - Create warehouse
GET    /api/v1/warehouses/{id}             - Get warehouse
PUT    /api/v1/warehouses/{id}             - Update warehouse
DELETE /api/v1/warehouses/{id}             - Delete warehouse
GET    /api/v1/warehouses/{id}/stock-summary - Get stock summary
```

### Stock Locations
```
GET    /api/v1/stock-locations                         - List locations
POST   /api/v1/stock-locations                         - Create location
GET    /api/v1/stock-locations/{id}                    - Get location
PUT    /api/v1/stock-locations/{id}                    - Update location
DELETE /api/v1/stock-locations/{id}                    - Delete location
GET    /api/v1/stock-locations/by-warehouse/{id}       - Get locations by warehouse
```

### Stock Levels
```
GET    /api/v1/stock-levels?product_id={id}&warehouse_id={id} - Get stock levels
POST   /api/v1/stock-levels/adjust                            - Adjust stock
```

### Stock Movements
```
GET    /api/v1/stock-movements                 - List movements
GET    /api/v1/stock-movements/{id}            - Get movement
POST   /api/v1/stock-movements/receive         - Receive stock
POST   /api/v1/stock-movements/ship            - Ship stock
POST   /api/v1/stock-movements/transfer        - Transfer stock
GET    /api/v1/stock-movements/history/{id}    - Get product movement history
```

## Database Schema

### Tables
- `products` - Product master data
- `product_categories` - Hierarchical product categories
- `unit_of_measures` - Units of measure with conversions
- `warehouses` - Warehouse definitions
- `stock_locations` - Warehouse locations
- `stock_levels` - Current stock by product/warehouse/location
- `stock_movements` - Inventory transactions

## Usage Examples

### Create a Product
```php
POST /api/v1/products
{
    "product_category_id": 1,
    "unit_of_measure_id": 1,
    "name": "Widget A",
    "sku": "WID-001",
    "product_type": "stockable",
    "status": "active",
    "list_price": 99.99,
    "cost_price": 50.00,
    "currency": "USD",
    "reorder_level": 10,
    "reorder_quantity": 50
}
```

### Receive Stock
```php
POST /api/v1/stock-movements/receive
{
    "product_id": 1,
    "warehouse_id": 1,
    "quantity": 100,
    "unit_cost": 50.00,
    "currency": "USD",
    "reference_type": "purchase_order",
    "reference_id": 123,
    "notes": "Received from supplier"
}
```

### Transfer Stock
```php
POST /api/v1/stock-movements/transfer
{
    "product_id": 1,
    "from_warehouse_id": 1,
    "to_warehouse_id": 2,
    "quantity": 25,
    "currency": "USD",
    "reason": "Stock rebalancing"
}
```

### Check Stock Availability
```php
GET /api/v1/stock-levels?product_id=1&warehouse_id=1
```

## Events

- `StockLevelChanged` - Fired when stock level is modified
- `LowStockAlert` - Fired when stock falls below reorder point
- `StockMovementRecorded` - Fired when a stock movement is recorded

## Services

### ProductService
Handles product CRUD operations and business logic.

### InventoryService
Manages stock availability, reservations, and queries.

### StockMovementService
Handles all stock movements (receive, ship, transfer, adjust).

### WarehouseService
Manages warehouse operations.

## Multi-Tenancy

All entities are tenant-aware and automatically scoped to the current tenant using the `Tenantable` trait.

## Security

All API endpoints require:
- Authentication via Sanctum (`auth:sanctum` middleware)
- Tenant context (`tenant` middleware)

## Testing

Run module tests:
```bash
php artisan test --testsuite=Inventory
```

## Installation

The module is automatically registered via the service provider. To seed initial data:

```bash
php artisan db:seed --class=Modules\\Inventory\\Database\\Seeders\\InventorySeeder
```

## Dependencies

- Core module (base traits and repositories)
- Tenancy module (multi-tenant support)

## Future Enhancements

- Batch and serial number tracking
- Barcode generation
- Stock forecasting
- Automated reordering
- Inventory valuation reports
- Cycle counting
- Warehouse optimization algorithms
