# API Quick Start Guide

## Overview

The AutoERP platform provides a comprehensive REST API built with Laravel 12.x. All endpoints follow RESTful conventions and return JSON responses.

## Base URL

```
Production: https://your-domain.com/api/v1
Development: http://localhost:8000/api/v1
```

## Authentication

The API uses **JWT (JSON Web Token)** authentication with stateless, multi-device support.

### Login

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "device_name": "Web Browser",
  "organization_id": 1
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "expires_at": "2026-02-12T19:22:50.000000Z",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

### Using the Token

Include the token in the `Authorization` header for all authenticated requests:

```http
GET /api/v1/products
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
X-Tenant-ID: 1
X-Organization-ID: 1
```

### Token Refresh

```http
POST /api/v1/auth/refresh
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Logout

```http
POST /api/v1/auth/logout
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

## Request Headers

All requests should include:

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
X-Organization-ID: {organization_id}
```

## Response Format

### Success Response

```json
{
  "data": {
    "id": 1,
    "name": "Product Name",
    "...": "..."
  },
  "meta": {
    "version": "1.0.0",
    "timestamp": "2026-02-11T19:22:50.000000Z"
  }
}
```

### Pagination Response

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  },
  "links": {
    "first": "https://api.example.com/products?page=1",
    "last": "https://api.example.com/products?page=5",
    "prev": null,
    "next": "https://api.example.com/products?page=2"
  }
}
```

### Error Response

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

## HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `204 No Content` - Request successful, no content returned
- `400 Bad Request` - Invalid request parameters
- `401 Unauthorized` - Authentication required or failed
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

## Core Modules & Endpoints

### 1. Product Management

**List Products**
```http
GET /api/v1/products?page=1&per_page=15&search=laptop
```

**Get Product**
```http
GET /api/v1/products/{id}
```

**Create Product**
```http
POST /api/v1/products
Content-Type: application/json

{
  "code": "PROD-001",
  "name": "Laptop Computer",
  "description": "High-performance laptop",
  "type": "goods",
  "category_id": 1,
  "buying_unit_id": 1,
  "selling_unit_id": 1,
  "status": "active",
  "properties": {
    "brand": "Dell",
    "model": "XPS 15"
  }
}
```

**Update Product**
```http
PUT /api/v1/products/{id}
```

**Delete Product**
```http
DELETE /api/v1/products/{id}
```

### 2. Pricing Management

**List Product Prices**
```http
GET /api/v1/products/{id}/prices
```

**Create Price**
```http
POST /api/v1/prices
Content-Type: application/json

{
  "product_id": 1,
  "location_id": 1,
  "strategy": "flat",
  "base_price": "999.99",
  "valid_from": "2026-01-01",
  "valid_to": "2026-12-31",
  "rules": {
    "discount_percent": 10
  }
}
```

### 3. CRM - Customer Management

**List Customers**
```http
GET /api/v1/customers?status=active&type=business
```

**Create Customer**
```http
POST /api/v1/customers
Content-Type: application/json

{
  "code": "CUST-001",
  "name": "Acme Corporation",
  "type": "business",
  "email": "contact@acme.com",
  "phone": "+1-555-0100",
  "billing_address": {
    "street": "123 Main St",
    "city": "New York",
    "state": "NY",
    "zip": "10001",
    "country": "USA"
  }
}
```

### 4. Sales - Orders & Invoices

**Create Quotation**
```http
POST /api/v1/quotations
Content-Type: application/json

{
  "customer_id": 1,
  "quotation_date": "2026-02-11",
  "valid_until": "2026-03-11",
  "items": [
    {
      "product_id": 1,
      "quantity": "10",
      "unit_price": "999.99",
      "tax_rate": "10",
      "discount_rate": "5"
    }
  ],
  "notes": "Volume discount applied"
}
```

**Create Order**
```http
POST /api/v1/orders
Content-Type: application/json

{
  "customer_id": 1,
  "quotation_id": 1,
  "order_date": "2026-02-11",
  "items": [...]
}
```

**Create Invoice**
```http
POST /api/v1/invoices
Content-Type: application/json

{
  "customer_id": 1,
  "order_id": 1,
  "invoice_date": "2026-02-11",
  "due_date": "2026-03-11",
  "items": [...]
}
```

**Record Payment**
```http
POST /api/v1/invoices/{id}/payments
Content-Type: application/json

{
  "amount": "9999.90",
  "payment_method": "bank_transfer",
  "payment_date": "2026-02-11",
  "reference": "TXN-12345",
  "notes": "Payment received"
}
```

### 5. Purchase Management

**Create Purchase Order**
```http
POST /api/v1/purchase-orders
Content-Type: application/json

{
  "vendor_id": 1,
  "po_date": "2026-02-11",
  "expected_delivery_date": "2026-02-20",
  "items": [
    {
      "product_id": 1,
      "quantity": "100",
      "unit_price": "500.00"
    }
  ]
}
```

**Record Goods Receipt**
```http
POST /api/v1/goods-receipts
Content-Type: application/json

{
  "purchase_order_id": 1,
  "receipt_date": "2026-02-20",
  "items": [
    {
      "product_id": 1,
      "quantity_received": "100"
    }
  ]
}
```

### 6. Inventory Management

**List Stock Levels**
```http
GET /api/v1/warehouses/{id}/stock?product_id=1
```

**Create Stock Movement**
```http
POST /api/v1/stock-movements
Content-Type: application/json

{
  "product_id": 1,
  "from_warehouse_id": 1,
  "to_warehouse_id": 2,
  "quantity": "50",
  "movement_type": "transfer",
  "movement_date": "2026-02-11"
}
```

**Initiate Stock Count**
```http
POST /api/v1/stock-counts
Content-Type: application/json

{
  "warehouse_id": 1,
  "count_date": "2026-02-11",
  "items": [
    {
      "product_id": 1,
      "expected_quantity": "100",
      "actual_quantity": "98"
    }
  ]
}
```

### 7. Accounting

**Get Chart of Accounts**
```http
GET /api/v1/accounts?type=asset
```

**Create Journal Entry**
```http
POST /api/v1/journal-entries
Content-Type: application/json

{
  "entry_date": "2026-02-11",
  "description": "Sales invoice #INV-001",
  "lines": [
    {
      "account_id": 1,
      "debit": "9999.90",
      "credit": "0.00"
    },
    {
      "account_id": 2,
      "debit": "0.00",
      "credit": "9999.90"
    }
  ]
}
```

**Generate Financial Reports**
```http
GET /api/v1/reports/trial-balance?from=2026-01-01&to=2026-12-31
GET /api/v1/reports/balance-sheet?as_of=2026-12-31
GET /api/v1/reports/income-statement?from=2026-01-01&to=2026-12-31
GET /api/v1/reports/cash-flow?from=2026-01-01&to=2026-12-31
```

## Advanced Features

### Filtering & Sorting

```http
GET /api/v1/products?filter[category_id]=1&filter[status]=active&sort=-created_at
```

### Field Selection

```http
GET /api/v1/products?fields=id,code,name,price
```

### Including Relationships

```http
GET /api/v1/products?include=category,prices,stock
```

### Search

```http
GET /api/v1/customers?search=acme&search_fields=name,email,phone
```

## Rate Limiting

- **Public endpoints**: 60 requests per minute
- **Authenticated endpoints**: 1000 requests per minute per user
- **Admin endpoints**: Unlimited

Rate limit headers:
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1707675770
```

## Webhooks

Subscribe to events:

```http
POST /api/v1/webhooks
Content-Type: application/json

{
  "url": "https://your-app.com/webhook",
  "events": ["invoice.created", "order.confirmed", "payment.received"],
  "secret": "your-webhook-secret"
}
```

## Error Handling

### Validation Errors (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "quantity": ["The quantity must be greater than 0."]
  }
}
```

### Not Found (404)

```json
{
  "message": "Resource not found."
}
```

### Unauthorized (401)

```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)

```json
{
  "message": "This action is unauthorized."
}
```

## Best Practices

1. **Use HTTPS** - Always use HTTPS in production
2. **Validate Input** - Validate all input data
3. **Handle Errors** - Implement proper error handling
4. **Cache Responses** - Use ETags and cache headers
5. **Pagination** - Always paginate large result sets
6. **Versioning** - API is versioned (currently v1)
7. **Idempotency** - Use idempotency keys for non-idempotent operations

## SDK & Libraries

### PHP
```bash
composer require kasunvimarshana/erp-api-client
```

### JavaScript
```bash
npm install @kasunvimarshana/erp-api-client
```

### Python
```bash
pip install erp-api-client
```

## Support

- **Documentation**: https://docs.your-domain.com
- **API Status**: https://status.your-domain.com
- **Support Email**: api-support@your-domain.com
- **GitHub Issues**: https://github.com/kasunvimarshana/AutoERP/issues

## Changelog

### v1.0.0
- Initial release
- Core modules: Product, Pricing, CRM, Sales, Purchase, Inventory, Accounting
- JWT authentication
- Multi-tenant support
- RESTful API design
