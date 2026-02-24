# Purchase Module

Purchase management module for managing vendors, purchase orders, and goods receipts.

## Features
- Vendor management with rating and status tracking
- Purchase order creation with line items and automatic total calculation using BCMath
- PO approval workflow
- Goods receipt (GRN) with line-level acceptance/rejection tracking
- Domain events for integration (triggers stock increase via Inventory module events)

## API Endpoints
- `GET|POST /api/v1/purchase/vendors`
- `GET|PUT|DELETE /api/v1/purchase/vendors/{id}`
- `GET|POST /api/v1/purchase/orders`
- `GET|PUT|DELETE /api/v1/purchase/orders/{id}`
- `POST /api/v1/purchase/orders/{id}/approve`
- `POST /api/v1/purchase/orders/{id}/receive`
- `GET /api/v1/purchase/receipts`
- `GET /api/v1/purchase/receipts/{id}`

## Architecture
Follows Clean Architecture with BCMath for all financial calculations and DB transactions for all writes.
