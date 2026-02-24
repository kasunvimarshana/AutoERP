# Sales Module

Sales management module for managing customers, quotations, and sales orders.

## Features
- Customer management (B2B/B2C)
- Quotation creation with line items and automatic total calculation
- Convert quotations to sales orders
- Order lifecycle management (draft → confirmed → shipped → invoiced → closed)
- BCMath-based financial calculations (no floating point)

## API Endpoints
- `GET|POST /api/v1/sales/customers`
- `GET|PUT|DELETE /api/v1/sales/customers/{id}`
- `GET|POST /api/v1/sales/quotations`
- `GET|PUT|DELETE /api/v1/sales/quotations/{id}`
- `POST /api/v1/sales/quotations/{id}/convert`
- `GET|POST /api/v1/sales/orders`
- `GET|PUT|DELETE /api/v1/sales/orders/{id}`
- `POST /api/v1/sales/orders/{id}/confirm`
- `POST /api/v1/sales/orders/{id}/cancel`
- `POST /api/v1/sales/orders/{id}/ship`

## Architecture
Follows Clean Architecture with BCMath for all financial calculations.
