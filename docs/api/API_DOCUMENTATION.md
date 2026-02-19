# API Documentation

## Base URL
```
{APP_URL}/api/v1
```

## Authentication

All API requests require JWT authentication via the `Authorization` header:

```
Authorization: Bearer {jwt_token}
```

### Authentication Endpoints

#### POST /auth/login
Login and get JWT token

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password",
  "device_id": "device-unique-id",
  "device_name": "iPhone 15 Pro",
  "device_type": "mobile"
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLC...",
  "user": {
    "id": "uuid",
    "name": "John Doe",
    "email": "user@example.com",
    "tenant_id": "uuid",
    "organization_id": "uuid"
  },
  "expires_in": 3600
}
```

#### POST /auth/refresh
Refresh JWT token

**Request:**
```json
{
  "token": "current-jwt-token"
}
```

**Response:**
```json
{
  "token": "new-jwt-token",
  "expires_in": 3600
}
```

#### POST /auth/logout
Logout and revoke token

**Request:**
```json
{
  "token": "jwt-token-to-revoke"
}
```

**Response:**
```json
{
  "message": "Successfully logged out"
}
```

## Tenant Management

### GET /tenants
List all tenants (admin only)

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Acme Corporation",
      "slug": "acme",
      "domain": "acme.example.com",
      "is_active": true,
      "created_at": "2026-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 1
  }
}
```

### POST /tenants
Create a new tenant (admin only)

**Request:**
```json
{
  "name": "New Company",
  "slug": "new-company",
  "domain": "newcompany.example.com",
  "settings": {
    "timezone": "America/New_York"
  }
}
```

## Organization Management

### GET /organizations
List organizations for current tenant

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Sales Department",
      "code": "SALES",
      "type": "department",
      "parent_id": null,
      "level": 0,
      "is_active": true
    }
  ]
}
```

### POST /organizations
Create a new organization

**Request:**
```json
{
  "name": "Marketing Team",
  "code": "MKTG",
  "type": "team",
  "parent_id": "parent-uuid",
  "metadata": {
    "location": "New York"
  }
}
```

## User Management

### GET /users
List users in current tenant

**Query Parameters:**
- `organization_id`: Filter by organization
- `is_active`: Filter by active status
- `page`: Page number
- `per_page`: Results per page

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Jane Smith",
      "email": "jane@example.com",
      "organization_id": "uuid",
      "is_active": true,
      "created_at": "2026-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

### POST /users
Create a new user

**Request:**
```json
{
  "name": "New User",
  "email": "newuser@example.com",
  "password": "secure-password",
  "organization_id": "uuid",
  "role_ids": ["role-uuid-1", "role-uuid-2"]
}
```

## Role & Permission Management

### GET /roles
List all roles

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Administrator",
      "slug": "admin",
      "description": "Full system access",
      "is_system": true,
      "permissions_count": 50
    }
  ]
}
```

### POST /roles
Create a new role

**Request:**
```json
{
  "name": "Sales Manager",
  "slug": "sales-manager",
  "description": "Manage sales operations",
  "permission_ids": ["perm-uuid-1", "perm-uuid-2"]
}
```

## Product Management

### GET /products
List products

**Query Parameters:**
- `type`: Filter by product type (good, service, bundle, composite)
- `category_id`: Filter by category
- `is_active`: Filter by active status
- `search`: Search in name/code
- `page`: Page number
- `per_page`: Results per page

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Product Name",
      "code": "PRD-00001",
      "type": "good",
      "description": "Product description",
      "category": {
        "id": "uuid",
        "name": "Electronics"
      },
      "buying_unit": {
        "id": "uuid",
        "name": "Box",
        "symbol": "box"
      },
      "selling_unit": {
        "id": "uuid",
        "name": "Piece",
        "symbol": "pc"
      },
      "is_active": true,
      "created_at": "2026-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 500
  }
}
```

### POST /products
Create a new product

**Request:**
```json
{
  "name": "New Product",
  "code": "PRD-00002",
  "type": "good",
  "description": "Product description",
  "category_id": "uuid",
  "buying_unit_id": "uuid",
  "selling_unit_id": "uuid",
  "metadata": {
    "sku": "SKU-12345"
  }
}
```

### GET /products/{id}
Get product details

**Response:**
```json
{
  "data": {
    "id": "uuid",
    "name": "Product Name",
    "code": "PRD-00001",
    "type": "bundle",
    "bundle_items": [
      {
        "product": {
          "id": "uuid",
          "name": "Item 1",
          "code": "PRD-00003"
        },
        "quantity": "2.000000"
      }
    ],
    "prices": [
      {
        "location_id": null,
        "strategy": "flat",
        "price": "99.99",
        "is_active": true
      }
    ]
  }
}
```

## Pricing Management

### GET /products/{id}/prices
Get product prices

**Query Parameters:**
- `location_id`: Filter by location

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "product_id": "uuid",
      "location_id": "uuid",
      "strategy": "tiered",
      "price": "100.00",
      "config": {
        "tiers": [
          {
            "min_quantity": "1",
            "price": "100.00"
          },
          {
            "min_quantity": "10",
            "price": "95.00"
          }
        ]
      },
      "valid_from": "2026-01-01T00:00:00Z",
      "valid_until": null,
      "is_active": true
    }
  ]
}
```

### POST /products/{id}/prices
Add product price

**Request:**
```json
{
  "location_id": "uuid",
  "strategy": "percentage",
  "price": "100.00",
  "config": {
    "percentage": "15",
    "is_markup": true
  },
  "valid_from": "2026-01-01T00:00:00Z",
  "valid_until": "2026-12-31T23:59:59Z"
}
```

### POST /pricing/calculate
Calculate price for product

**Request:**
```json
{
  "product_id": "uuid",
  "quantity": "5",
  "location_id": "uuid"
}
```

**Response:**
```json
{
  "data": {
    "product_id": "uuid",
    "quantity": "5",
    "unit_price": "100.000000",
    "total_price": "500.000000",
    "strategy": "flat",
    "location_id": "uuid"
  }
}
```

## Audit Logs

### GET /audit-logs
Get audit logs

**Query Parameters:**
- `event`: Filter by event type
- `auditable_type`: Filter by model type
- `user_id`: Filter by user
- `from_date`: Start date
- `to_date`: End date
- `page`: Page number
- `per_page`: Results per page

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "event": "updated",
      "auditable_type": "Product",
      "auditable_id": "uuid",
      "user": {
        "id": "uuid",
        "name": "John Doe"
      },
      "old_values": {
        "price": "100.00"
      },
      "new_values": {
        "price": "120.00"
      },
      "ip_address": "192.168.1.1",
      "created_at": "2026-02-10T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1000
  }
}
```

## Error Responses

All error responses follow this format:

```json
{
  "error": {
    "message": "Error message",
    "code": "ERROR_CODE",
    "details": {
      "field": ["Validation error message"]
    }
  }
}
```

### HTTP Status Codes

- `200 OK`: Successful request
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request data
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `409 Conflict`: Optimistic lock failure
- `422 Unprocessable Entity`: Validation error
- `500 Internal Server Error`: Server error

## Rate Limiting

API requests are rate-limited per tenant:
- **Limit**: 1000 requests per minute
- **Headers**: 
  - `X-RateLimit-Limit`: Request limit
  - `X-RateLimit-Remaining`: Remaining requests
  - `X-RateLimit-Reset`: Reset timestamp

## Pagination

All list endpoints support pagination:

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Results per page (default: 15, max: 100)

**Response Meta:**
```json
{
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

## Filtering & Sorting

**Query Parameters:**
- `filter[field]`: Filter by field value
- `sort`: Sort field (prefix with `-` for descending)
- `include`: Include related resources

**Example:**
```
GET /products?filter[type]=good&sort=-created_at&include=category,prices
```

## Webhooks

Configure webhooks to receive real-time notifications:

**Events:**
- `product.created`
- `product.updated`
- `product.deleted`
- `price.created`
- `price.updated`
- `user.created`
- `order.created` (future)

**Webhook Payload:**
```json
{
  "event": "product.updated",
  "tenant_id": "uuid",
  "timestamp": "2026-02-10T10:30:00Z",
  "data": {
    "product_id": "uuid",
    "changes": {
      "price": {
        "old": "100.00",
        "new": "120.00"
      }
    }
  }
}
```

## Notes

- All timestamps are in UTC
- All monetary values are strings for precision
- All IDs are UUIDs
- All requests must include `Accept: application/json` header
- All POST/PUT/PATCH requests must include `Content-Type: application/json` header

---

For implementation details, see `ARCHITECTURE.md`
