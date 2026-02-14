# AutoERP API Reference

## Overview

The AutoERP API is a RESTful API that provides programmatic access to all system functionality. All API endpoints return JSON responses and use standard HTTP status codes for errors.

## Base URL

```
Production: https://api.autoerp.com/api/v1
Development: http://localhost:8080/api/v1
```

## Authentication

The API uses token-based authentication with Laravel Sanctum. Include the token in the `Authorization` header:

```
Authorization: Bearer YOUR_API_TOKEN
```

### Obtaining an API Token

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz"
  }
}
```

## Headers

All requests should include:

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer YOUR_API_TOKEN
X-Tenant-ID: tenant_uuid (for multi-tenant operations)
```

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": ["Error description"]
  },
  "code": 422
}
```

## HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `204 No Content` - Request successful, no content to return
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

## Pagination

List endpoints support pagination with the following parameters:

- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

Example:
```
GET /api/v1/customers?page=2&per_page=20
```

## Filtering

Use query parameters for filtering:

```
GET /api/v1/customers?status=active&type=business
```

## Sorting

Use `sort` and `order` parameters:

```
GET /api/v1/customers?sort=created_at&order=desc
```

## Field Selection

Use `fields` parameter for sparse fieldsets:

```
GET /api/v1/customers?fields=id,name,email
```

## Including Related Data

Use `include` parameter for eager loading:

```
GET /api/v1/customers?include=contacts,addresses
```

---

## Endpoints

### Authentication

#### Login

```http
POST /api/v1/auth/login
```

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "John Doe",
      "email": "user@example.com",
      "tenant_id": 1
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz"
  }
}
```

#### Register

```http
POST /api/v1/auth/register
```

**Request:**
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Logout

```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

#### Get Current User

```http
GET /api/v1/auth/user
Authorization: Bearer {token}
```

### Tenants

#### List Tenants (Admin only)

```http
GET /api/v1/tenants
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Acme Corporation",
      "slug": "acme",
      "domain": "acme.autoerp.com",
      "status": "active",
      "subscription": {
        "plan": "professional",
        "status": "active",
        "expires_at": "2027-01-31T00:00:00Z"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 50
  }
}
```

#### Create Tenant

```http
POST /api/v1/tenants
```

**Request:**
```json
{
  "name": "Acme Corporation",
  "slug": "acme",
  "email": "admin@acme.com",
  "plan_id": 2,
  "trial": true
}
```

#### Get Tenant

```http
GET /api/v1/tenants/{id}
```

#### Update Tenant

```http
PUT /api/v1/tenants/{id}
```

**Request:**
```json
{
  "name": "Acme Corporation Updated",
  "status": "active"
}
```

#### Suspend Tenant

```http
POST /api/v1/tenants/{id}/suspend
```

#### Activate Tenant

```http
POST /api/v1/tenants/{id}/activate
```

### Users

#### List Users

```http
GET /api/v1/users
```

**Query Parameters:**
- `status` - Filter by status (active, inactive, suspended)
- `role` - Filter by role name
- `search` - Search by name or email

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "John Doe",
      "email": "john@example.com",
      "status": "active",
      "roles": ["admin", "manager"],
      "organization": {
        "id": 1,
        "name": "Main Organization"
      },
      "branch": {
        "id": 1,
        "name": "Main Branch"
      },
      "last_login_at": "2026-01-31T12:00:00Z",
      "created_at": "2026-01-01T00:00:00Z"
    }
  ]
}
```

#### Create User

```http
POST /api/v1/users
```

**Request:**
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "securePassword123",
  "organization_id": 1,
  "branch_id": 1,
  "roles": ["manager"],
  "status": "active"
}
```

#### Get User

```http
GET /api/v1/users/{id}
```

#### Update User

```http
PUT /api/v1/users/{id}
```

**Request:**
```json
{
  "name": "Jane Smith Updated",
  "status": "active",
  "roles": ["manager", "sales"]
}
```

#### Delete User

```http
DELETE /api/v1/users/{id}
```

### Customers

#### List Customers

```http
GET /api/v1/customers
```

**Query Parameters:**
- `type` - Filter by type (individual, business)
- `status` - Filter by status (active, inactive)
- `search` - Search by name, email, or code
- `tag` - Filter by tag

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "code": "CUST-001",
      "type": "business",
      "company_name": "XYZ Corp",
      "email": "contact@xyz.com",
      "phone": "+1234567890",
      "credit_limit": 50000.00,
      "payment_terms": 30,
      "status": "active",
      "contacts": [
        {
          "id": 1,
          "name": "John Contact",
          "email": "john@xyz.com",
          "is_primary": true
        }
      ],
      "addresses": [
        {
          "id": 1,
          "type": "billing",
          "address_line1": "123 Main St",
          "city": "New York",
          "country": "USA",
          "is_default": true
        }
      ]
    }
  ]
}
```

#### Create Customer

```http
POST /api/v1/customers
```

**Request (Business):**
```json
{
  "type": "business",
  "company_name": "XYZ Corporation",
  "email": "contact@xyz.com",
  "phone": "+1234567890",
  "tax_number": "123456789",
  "credit_limit": 50000.00,
  "payment_terms": 30,
  "contacts": [
    {
      "name": "John Contact",
      "title": "Manager",
      "email": "john@xyz.com",
      "phone": "+1234567891",
      "is_primary": true
    }
  ],
  "addresses": [
    {
      "type": "billing",
      "address_line1": "123 Main St",
      "city": "New York",
      "state": "NY",
      "postal_code": "10001",
      "country": "USA",
      "is_default": true
    }
  ]
}
```

**Request (Individual):**
```json
{
  "type": "individual",
  "first_name": "Jane",
  "last_name": "Doe",
  "email": "jane@example.com",
  "phone": "+1234567890",
  "credit_limit": 10000.00,
  "payment_terms": 15
}
```

#### Get Customer

```http
GET /api/v1/customers/{id}?include=contacts,addresses,tags
```

#### Update Customer

```http
PUT /api/v1/customers/{id}
```

#### Delete Customer

```http
DELETE /api/v1/customers/{id}
```

#### Search Customers

```http
GET /api/v1/customers/search?q=acme
```

### Products

#### List Products

```http
GET /api/v1/products
```

**Query Parameters:**
- `category_id` - Filter by category
- `brand_id` - Filter by brand
- `status` - Filter by status (active, inactive)
- `track_inventory` - Filter by inventory tracking (true, false)
- `low_stock` - Show only low stock items (true)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "sku": "PROD-001",
      "name": "Product Name",
      "description": "Product description",
      "category": {
        "id": 1,
        "name": "Electronics"
      },
      "brand": {
        "id": 1,
        "name": "Samsung"
      },
      "unit_price": 99.99,
      "cost_price": 50.00,
      "unit_of_measure": "pcs",
      "track_inventory": true,
      "track_batch": true,
      "track_serial": false,
      "track_expiry": false,
      "min_stock_level": 10,
      "reorder_point": 20,
      "status": "active"
    }
  ]
}
```

#### Create Product

```http
POST /api/v1/products
```

**Request:**
```json
{
  "sku": "PROD-002",
  "name": "New Product",
  "description": "Product description",
  "category_id": 1,
  "brand_id": 1,
  "unit_price": 199.99,
  "cost_price": 100.00,
  "unit_of_measure": "pcs",
  "track_inventory": true,
  "track_batch": true,
  "min_stock_level": 10,
  "reorder_point": 20,
  "status": "active"
}
```

#### Get Product

```http
GET /api/v1/products/{id}
```

#### Update Product

```http
PUT /api/v1/products/{id}
```

#### Delete Product

```http
DELETE /api/v1/products/{id}
```

#### Get Product Stock

```http
GET /api/v1/products/{id}/stock
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "product_id": 1,
    "warehouses": [
      {
        "warehouse_id": 1,
        "warehouse_name": "Main Warehouse",
        "available_quantity": 150,
        "total_value": 7500.00,
        "batches": [
          {
            "batch_number": "BATCH-001",
            "quantity": 100,
            "manufacture_date": "2026-01-01",
            "expiry_date": null
          }
        ]
      }
    ],
    "total_quantity": 150,
    "total_value": 7500.00
  }
}
```

### Inventory

#### Record Incoming Stock

```http
POST /api/v1/inventory/incoming
```

**Request:**
```json
{
  "product_id": 1,
  "warehouse_id": 1,
  "location_id": 1,
  "quantity": 100,
  "unit_cost": 50.00,
  "batch_number": "BATCH-001",
  "manufacture_date": "2026-01-01",
  "expiry_date": "2027-01-01",
  "reference_type": "purchase_order",
  "reference_id": 123,
  "notes": "Received from supplier"
}
```

#### Record Outgoing Stock

```http
POST /api/v1/inventory/outgoing
```

**Request:**
```json
{
  "product_id": 1,
  "warehouse_id": 1,
  "quantity": 10,
  "reference_type": "sales_order",
  "reference_id": 456,
  "notes": "Shipped to customer"
}
```

**Note:** The system automatically allocates stock using FIFO/FEFO based on product configuration.

#### Get Stock Levels

```http
GET /api/v1/inventory/stock-levels
```

**Query Parameters:**
- `product_id` - Filter by product
- `warehouse_id` - Filter by warehouse
- `low_stock` - Show only low stock (true)

#### Get Stock Movements

```http
GET /api/v1/inventory/movements
```

**Query Parameters:**
- `product_id` - Filter by product
- `warehouse_id` - Filter by warehouse
- `transaction_type` - Filter by type
- `from_date` - Start date
- `to_date` - End date

#### Get Expiry Alerts

```http
GET /api/v1/inventory/expiry-alerts?days=30
```

Returns products expiring within specified days.

### Invoices

#### List Invoices

```http
GET /api/v1/invoices
```

**Query Parameters:**
- `customer_id` - Filter by customer
- `status` - Filter by status (draft, sent, paid, overdue, cancelled)
- `from_date` - Start date
- `to_date` - End date

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "invoice_number": "INV-001",
      "customer": {
        "id": 1,
        "name": "XYZ Corp"
      },
      "issue_date": "2026-01-15",
      "due_date": "2026-02-14",
      "subtotal": 1000.00,
      "tax_amount": 100.00,
      "discount_amount": 50.00,
      "total_amount": 1050.00,
      "paid_amount": 1050.00,
      "status": "paid",
      "items": [
        {
          "id": 1,
          "product_id": 1,
          "description": "Product Name",
          "quantity": 10,
          "unit_price": 100.00,
          "tax_rate": 10.00,
          "total": 1000.00
        }
      ]
    }
  ]
}
```

#### Create Invoice

```http
POST /api/v1/invoices
```

**Request:**
```json
{
  "customer_id": 1,
  "issue_date": "2026-01-31",
  "due_date": "2026-02-28",
  "items": [
    {
      "product_id": 1,
      "description": "Product Name",
      "quantity": 10,
      "unit_price": 100.00,
      "tax_rate": 10.00
    }
  ],
  "notes": "Thank you for your business"
}
```

#### Get Invoice

```http
GET /api/v1/invoices/{id}
```

#### Update Invoice

```http
PUT /api/v1/invoices/{id}
```

#### Delete Invoice

```http
DELETE /api/v1/invoices/{id}
```

#### Send Invoice

```http
POST /api/v1/invoices/{id}/send
```

Sends invoice to customer email.

#### Record Payment

```http
POST /api/v1/invoices/{id}/payments
```

**Request:**
```json
{
  "amount": 1050.00,
  "payment_date": "2026-01-31",
  "payment_method": "bank_transfer",
  "reference_number": "TXN123456",
  "notes": "Payment received"
}
```

### Vehicles

#### List Vehicles

```http
GET /api/v1/vehicles
```

**Query Parameters:**
- `customer_id` - Filter by customer
- `status` - Filter by status (active, sold, scrapped)
- `search` - Search by registration, VIN

#### Create Vehicle

```http
POST /api/v1/vehicles
```

**Request:**
```json
{
  "customer_id": 1,
  "registration_number": "ABC-1234",
  "vin": "1HGCM82633A123456",
  "make": "Honda",
  "model": "Accord",
  "year": 2023,
  "color": "Silver",
  "fuel_type": "petrol",
  "transmission": "automatic",
  "odometer_reading": 5000,
  "warranty_expires_at": "2026-12-31",
  "insurance_expires_at": "2026-12-31"
}
```

#### Get Vehicle

```http
GET /api/v1/vehicles/{id}
```

#### Update Vehicle

```http
PUT /api/v1/vehicles/{id}
```

#### Get Service History

```http
GET /api/v1/vehicles/{id}/service-history
```

#### Record Service

```http
POST /api/v1/vehicles/{id}/service
```

**Request:**
```json
{
  "service_date": "2026-01-31",
  "service_type": "routine",
  "odometer_reading": 10000,
  "description": "Oil change and filter replacement",
  "parts_used": "Oil filter, Engine oil",
  "labor_cost": 50.00,
  "parts_cost": 30.00
}
```

---

## Rate Limiting

API requests are rate limited per tenant:

- **Free Tier**: 60 requests per minute
- **Basic Tier**: 120 requests per minute
- **Professional Tier**: 300 requests per minute
- **Enterprise Tier**: Unlimited

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1643731200
```

## Webhooks

AutoERP supports webhooks for real-time event notifications.

### Available Events

- `customer.created`
- `customer.updated`
- `invoice.created`
- `invoice.paid`
- `payment.received`
- `stock.low_level`
- `stock.expired`

### Webhook Payload

```json
{
  "event": "invoice.paid",
  "timestamp": "2026-01-31T12:00:00Z",
  "data": {
    "invoice_id": 123,
    "amount": 1050.00
  }
}
```

## SDKs and Client Libraries

Official SDKs are available for:

- **PHP**: `composer require autoerp/php-sdk`
- **JavaScript/Node.js**: `npm install @autoerp/js-sdk`
- **Python**: `pip install autoerp-sdk`

## Support

For API support:
- Email: api-support@autoerp.com
- Documentation: https://docs.autoerp.com
- Slack: autoerp.slack.com

---

**Last Updated**: 2026-01-31
**Version**: 1.0.0
