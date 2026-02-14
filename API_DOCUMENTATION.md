# AutoERP - API Documentation

## Base URL
```
Development: http://localhost:8000/api
Production: https://your-domain.com/api
```

## API Version
Current version: `v1`

All endpoints are prefixed with `/v1/`

## Authentication

The API uses Bearer token authentication via Laravel Sanctum.

### Get Token
Include the token in all authenticated requests:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

## Response Format

### Success Response
```json
{
  "data": {
    "id": 1,
    "name": "Resource Name"
  },
  "message": "Operation successful"
}
```

### Error Response
```json
{
  "message": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## Status Codes

- `200` - OK
- `201` - Created
- `204` - No Content
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Unprocessable Entity (Validation Error)
- `500` - Internal Server Error

---

## Authentication Endpoints

### Register User
```http
POST /v1/auth/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "tenant_id": 1 // Optional
}
```

**Response:** `201 Created`
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "tenant_id": null
  }
}
```

### Login
```http
POST /v1/auth/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:** `200 OK`
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "tenant_id": 1
  },
  "token": "1|abc123token..."
}
```

### Logout
```http
POST /v1/auth/logout
```
**Authentication:** Required

**Response:** `200 OK`
```json
{
  "message": "Logged out successfully"
}
```

### Get Current User
```http
GET /v1/auth/me
```
**Authentication:** Required

**Response:** `200 OK`
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "tenant": {
      "id": 1,
      "name": "My Company"
    }
  }
}
```

---

## Tenant Management

### List Tenants
```http
GET /v1/tenants
```
**Authentication:** Required

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "name": "Company A",
      "slug": "company-a",
      "is_active": true,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Get Tenant
```http
GET /v1/tenants/{id}
```
**Authentication:** Required

**Response:** `200 OK`

### Create Tenant
```http
POST /v1/tenants
```
**Authentication:** Required

**Request Body:**
```json
{
  "name": "New Company",
  "slug": "new-company",
  "domain": "newcompany.example.com",
  "settings": {
    "timezone": "UTC",
    "currency": "USD"
  }
}
```

**Response:** `201 Created`

### Update Tenant
```http
PUT /v1/tenants/{id}
```
**Authentication:** Required

**Request Body:**
```json
{
  "name": "Updated Company Name",
  "is_active": true
}
```

**Response:** `200 OK`

### Delete Tenant
```http
DELETE /v1/tenants/{id}
```
**Authentication:** Required

**Response:** `200 OK`

---

## Product Management

### List Products
```http
GET /v1/products
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "sku": "PROD-001",
      "name": "Product Name",
      "cost_price": "100.00",
      "selling_price": "150.00",
      "is_active": true
    }
  ]
}
```

### Get Product
```http
GET /v1/products/{id}
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Response:** `200 OK`

### Create Product
```http
POST /v1/products
```
**Authentication:** Required  
**Tenant-aware:** Yes (auto-assigned)

**Request Body:**
```json
{
  "sku": "PROD-002",
  "name": "New Product",
  "description": "Product description",
  "category": "Electronics",
  "brand": "Brand Name",
  "unit_of_measure": "pcs",
  "cost_price": 100.00,
  "selling_price": 150.00,
  "tax_rate": 10.00,
  "barcode": "1234567890",
  "attributes": {
    "color": "blue",
    "size": "M"
  }
}
```

**Response:** `201 Created`

### Update Product
```http
PUT /v1/products/{id}
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Request Body:**
```json
{
  "name": "Updated Product Name",
  "selling_price": 160.00
}
```

**Response:** `200 OK`

### Delete Product
```http
DELETE /v1/products/{id}
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Response:** `200 OK`

---

## Inventory Management

### List Inventory Items
```http
GET /v1/inventory
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "branch_id": 1,
      "quantity": "100.00",
      "available_quantity": "90.00",
      "reserved_quantity": "10.00",
      "product": {
        "id": 1,
        "name": "Product Name"
      }
    }
  ]
}
```

### Get Inventory Item
```http
GET /v1/inventory/{id}
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Response:** `200 OK`

### Record Inventory Movement
```http
POST /v1/inventory/movements
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Request Body:**
```json
{
  "product_id": 1,
  "branch_id": 1,
  "movement_type": "in",
  "quantity": 50,
  "batch_number": "BATCH-001",
  "reference_type": "purchase_order",
  "reference_id": 123,
  "notes": "Stock replenishment"
}
```

**Movement Types:**
- `in` - Stock incoming
- `out` - Stock outgoing
- `transfer` - Stock transfer between branches
- `adjustment` - Inventory adjustment
- `return` - Stock return

**Response:** `201 Created`
```json
{
  "message": "Inventory movement recorded successfully",
  "data": {
    "id": 1,
    "product_id": 1,
    "movement_type": "in",
    "quantity": "50.00",
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

---

## Customer Management (CRM)

### List Customers
```http
GET /v1/customers
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Status:** Stub (Returns placeholder message)

### Create Customer
```http
POST /v1/customers
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Status:** To be implemented

---

## Invoice Management

### List Invoices
```http
GET /v1/invoices
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Status:** Stub (Returns placeholder message)

### Create Invoice
```http
POST /v1/invoices
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Status:** To be implemented

---

## Analytics

### Dashboard Statistics
```http
GET /v1/analytics/dashboard
```
**Authentication:** Required  
**Tenant-aware:** Yes

**Response:** `200 OK`
```json
{
  "message": "Analytics dashboard",
  "data": {
    "total_revenue": 0,
    "total_customers": 0,
    "total_products": 0,
    "total_invoices": 0
  }
}
```

---

## Error Handling

### Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Unauthorized Error
```json
{
  "message": "Unauthenticated."
}
```

### Not Found Error
```json
{
  "message": "Resource not found"
}
```

### Server Error
```json
{
  "message": "Internal server error"
}
```

---

## Rate Limiting

API endpoints are rate limited to:
- **60 requests per minute** for authenticated users
- **10 requests per minute** for unauthenticated users

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640000000
```

---

## Pagination

List endpoints support pagination:

```http
GET /v1/products?page=2&per_page=20
```

**Response includes:**
```json
{
  "data": [...],
  "meta": {
    "current_page": 2,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}
```

---

## Filtering & Sorting

### Filtering
```http
GET /v1/products?category=electronics&is_active=true
```

### Sorting
```http
GET /v1/products?sort_by=created_at&order=desc
```

---

## CORS

Cross-Origin Resource Sharing is configured for:
- Frontend URL from `FRONTEND_URL` env variable
- Default: `http://localhost:3000`

---

## Testing the API

### Using cURL
```bash
# Health check
curl http://localhost:8000/api/health

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Authenticated request
curl http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Using Postman
1. Import the API collection
2. Set base URL: `http://localhost:8000/api`
3. Add Bearer token in Authorization tab
4. Test endpoints

---

## Versioning

Current version: `v1`

When breaking changes are needed, a new version (`v2`) will be created.

Old versions remain available:
- `/v1/...` - Version 1
- `/v2/...` - Version 2 (future)

---

## Support

For issues or questions:
- GitHub Issues: [repository-url]
- Documentation: See INSTALLATION.md and ARCHITECTURE.md
- Email: support@example.com
