# Inventory Module

Inventory management module for products, warehouses, locations, and stock movements.

## Features
- Product management with categories, variants, and UOM support
- Multi-warehouse and hierarchical location management
- Stock movement tracking (receipt, delivery, transfer, adjustment, scrap)
- Pessimistic locking for concurrent stock operations
- BCMath for all quantity and cost calculations
- Reorder rules and low stock alerts via domain events

## API Endpoints
- `GET|POST /api/v1/inventory/products`
- `GET|PUT|DELETE /api/v1/inventory/products/{id}`
- `GET|POST /api/v1/inventory/categories`
- `GET|PUT|DELETE /api/v1/inventory/categories/{id}`
- `GET|POST /api/v1/inventory/warehouses`
- `GET|PUT|DELETE /api/v1/inventory/warehouses/{id}`
- `GET|POST /api/v1/inventory/locations`
- `GET|PUT|DELETE /api/v1/inventory/locations/{id}`
- `GET|POST /api/v1/inventory/movements`
- `GET /api/v1/inventory/movements/{id}`
- `GET /api/v1/inventory/stock-levels`

## Architecture
Clean Architecture with pessimistic locking (SELECT FOR UPDATE) on all stock level mutations.
