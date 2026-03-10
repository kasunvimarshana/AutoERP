# API Contracts

Complete endpoint reference for all KV MultiTenant SaaS microservices.

> **Base URL (external):** `http://localhost:8080`  
> All authenticated endpoints require `Authorization: Bearer <jwt_token>` header.  
> All tenant-scoped endpoints require `X-Tenant-ID` header (injected by API Gateway from JWT).

---

## API Gateway

### Health Check

```
GET /health
```

**Response 200**
```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T10:00:00.000Z",
  "services": [
    { "name": "orders",        "url": "http://order-service:8000" },
    { "name": "payments",      "url": "http://payment-service:8000" },
    { "name": "inventory",     "url": "http://inventory-service:8000" },
    { "name": "notifications", "url": "http://notification-service:3001" }
  ]
}
```

### Login (Public)

```
POST /api/auth/login
```

Proxied to Order Service. Used to obtain JWT tokens for demo purposes.

**Request Body**
```json
{
  "email": "user@tenant.com",
  "password": "secret"
}
```

**Response 200**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "usr-123",
    "tenantId": "tenant-abc",
    "role": "admin"
  }
}
```

---

## Order Service (Laravel/PHP + MySQL)

**Internal Base URL:** `http://order-service:8000/api`

### List Orders

```
GET /api/orders
```

Headers: `Authorization: Bearer <token>`, `X-Tenant-ID: <tenant>`

**Response 200**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "tenant_id": "tenant-abc",
      "customer_id": "cust-001",
      "status": "confirmed",
      "total_amount": "1599.98",
      "currency": "USD",
      "items": [
        { "sku": "LAPTOP-001", "quantity": 1, "price": 1499.99 },
        { "sku": "KEYBOARD-01", "quantity": 1, "price": 89.99 }
      ],
      "saga_id": "saga-7f3e4a2b-...",
      "saga_status": "completed",
      "payment_id": "9a1b2c3d-...",
      "metadata": null,
      "created_at": "2024-01-15T09:30:00.000000Z",
      "updated_at": "2024-01-15T09:30:05.000000Z"
    }
  ],
  "total": 1,
  "per_page": 15
}
```

### Create Order

```
POST /api/orders
```

**Request Body**
```json
{
  "customer_id": "cust-001",
  "items": [
    { "sku": "LAPTOP-001",  "quantity": 1, "price": 1499.99 },
    { "sku": "KEYBOARD-01", "quantity": 1, "price": 89.99 }
  ],
  "currency": "USD"
}
```

**Response 201**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "tenant_id": "tenant-abc",
  "customer_id": "cust-001",
  "status": "confirmed",
  "total_amount": "1589.98",
  "currency": "USD",
  "items": [...],
  "saga_id": "saga-7f3e4a2b-...",
  "saga_status": "completed",
  "payment_id": "9a1b2c3d-...",
  "created_at": "2024-01-15T09:30:00.000000Z",
  "updated_at": "2024-01-15T09:30:05.000000Z"
}
```

**Validation Errors (422)**
```json
{
  "errors": {
    "customer_id": ["The customer id field is required."],
    "items": ["The items field is required."]
  }
}
```

### Get Order

```
GET /api/orders/{id}
```

**Response 200** – Single order object (see Create Order response).  
**Response 404** – Order not found or belongs to different tenant.

### Cancel Order

```
POST /api/orders/{id}/cancel
```

**Response 200** – Updated order with status `cancelled` or `compensated`.

**Response 409**
```json
{
  "error": "Order cannot be cancelled in its current state."
}
```

### Saga Callback (Internal)

```
POST /api/orders/{id}/saga-callback
```

Headers: `X-Internal-Service: <secret>`

**Request Body**
```json
{
  "event": "payment_processed",
  "data": {
    "payment_id": "9a1b2c3d-..."
  }
}
```

Valid events: `payment_processed`, `payment_failed`, `inventory_reserved`, `inventory_failed`

### Health Check

```
GET /api/health
```

**Response 200**
```json
{
  "service": "order-service",
  "status": "healthy",
  "timestamp": "2024-01-15T10:00:00+00:00"
}
```

---

## Payment Service (Laravel/PHP + PostgreSQL)

**Internal Base URL:** `http://payment-service:8000/api`

### Charge Payment (Saga Step 3)

```
POST /api/payments/charge
```

Called by Order Service. Not exposed through API Gateway.

**Request Body**
```json
{
  "order_id":    "550e8400-e29b-41d4-a716-446655440000",
  "saga_id":     "saga-7f3e4a2b-...",
  "tenant_id":   "tenant-abc",
  "customer_id": "cust-001",
  "amount":      1589.98,
  "currency":    "USD"
}
```

**Response 200 (success)**
```json
{
  "payment_id": "9a1b2c3d-e5f6-7890-abcd-ef1234567890",
  "status": "processed"
}
```

**Response 402 (failure)**
```json
{
  "error": "Payment processing failed.",
  "payment_id": "9a1b2c3d-..."
}
```

### Refund Payment (Saga Compensation)

```
POST /api/payments/{id}/refund
```

**Request Body**
```json
{
  "order_id": "550e8400-...",
  "reason": "customer_cancellation"
}
```

**Response 200**
```json
{
  "refund_id": "REF-ABCDEF123456",
  "status": "refunded"
}
```

**Response 409**
```json
{
  "error": "Payment is not in a refundable state."
}
```

### Get Payment

```
GET /api/payments/{id}
```

**Response 200**
```json
{
  "id": "9a1b2c3d-e5f6-7890-abcd-ef1234567890",
  "tenant_id": "tenant-abc",
  "order_id": "550e8400-...",
  "customer_id": "cust-001",
  "amount": "1589.98",
  "currency": "USD",
  "status": "processed",
  "saga_id": "saga-7f3e4a2b-...",
  "provider_reference": "PAY-ABCDEF123456",
  "refund_id": null,
  "metadata": null,
  "created_at": "2024-01-15T09:30:02.000000Z",
  "updated_at": "2024-01-15T09:30:02.000000Z"
}
```

### Health Check

```
GET /api/health
```

---

## Inventory Service (Python/FastAPI + MongoDB)

**Internal Base URL:** `http://inventory-service:8000/api`

### List Inventory

```
GET /api/inventory
```

**Response 200**
```json
{
  "items": [
    { "sku": "LAPTOP-001",  "name": "Laptop Pro 16",   "quantity": 49,  "price": 1499.99 },
    { "sku": "PHONE-001",   "name": "Smartphone X12",  "quantity": 200, "price": 799.99  },
    { "sku": "TABLET-001",  "name": "Tablet Air 11",   "quantity": 100, "price": 499.99  },
    { "sku": "MONITOR-001", "name": "4K Monitor 27\"", "quantity": 30,  "price": 349.99  },
    { "sku": "KEYBOARD-01", "name": "Mechanical KB",   "quantity": 149, "price": 89.99   }
  ],
  "count": 5
}
```

### Get Product by SKU

```
GET /api/inventory/{sku}
```

**Response 200**
```json
{
  "sku": "LAPTOP-001",
  "name": "Laptop Pro 16",
  "quantity": 49,
  "price": 1499.99
}
```

**Response 404**
```json
{
  "detail": "SKU 'LAPTOP-999' not found."
}
```

### Reserve Inventory (Saga Step 2)

```
POST /api/inventory/reserve
```

**Request Body**
```json
{
  "order_id":  "550e8400-e29b-41d4-a716-446655440000",
  "saga_id":   "saga-7f3e4a2b-...",
  "tenant_id": "tenant-abc",
  "items": [
    { "sku": "LAPTOP-001",  "quantity": 1, "price": 1499.99 },
    { "sku": "KEYBOARD-01", "quantity": 1, "price": 89.99 }
  ]
}
```

**Response 200**
```json
{
  "status": "reserved",
  "order_id": "550e8400-...",
  "items": [
    { "sku": "LAPTOP-001",  "quantity": 1 },
    { "sku": "KEYBOARD-01", "quantity": 1 }
  ]
}
```

**Response 409 (insufficient stock)**
```json
{
  "detail": "Insufficient stock for SKU 'LAPTOP-001'. Available: 0, requested: 1."
}
```

### Release Inventory (Saga Compensation)

```
POST /api/inventory/release
```

**Request Body**
```json
{
  "order_id":  "550e8400-...",
  "saga_id":   "saga-7f3e4a2b-...",
  "tenant_id": "tenant-abc",
  "reason":    "payment_failed"
}
```

**Response 200**
```json
{
  "status": "released",
  "order_id": "550e8400-..."
}
```

### Update Stock (Admin)

```
PATCH /api/inventory/{sku}/stock
```

**Request Body**
```json
{
  "delta": 50
}
```

Positive `delta` adds stock; negative subtracts.

**Response 200** – Updated product object.

### Health Check

```
GET /health
```

---

## Notification Service (Node.js + Redis)

**Internal Base URL:** `http://notification-service:3001`

### Send Notification

```
POST /api/notifications/send
```

**Request Body**
```json
{
  "tenant_id":      "tenant-abc",
  "customer_id":    "cust-001",
  "customer_email": "customer@example.com",
  "order_id":       "550e8400-...",
  "event":          "order_confirmed",
  "amount":         1589.98
}
```

Supported events: `order_confirmed`, `order_failed`, `payment_processed`, `payment_failed`

**Response 200 (sent)**
```json
{
  "status": "sent",
  "messageId": "<abc123@ethereal.email>"
}
```

**Response 200 (deduplicated)**
```json
{
  "status": "deduplicated"
}
```

**Response 200 (unknown event)**
```json
{
  "status": "unknown_event"
}
```

### Health Check

```
GET /health
```

**Response 200**
```json
{
  "service": "notification-service",
  "status": "healthy",
  "timestamp": "2024-01-15T10:00:00.000Z"
}
```

---

## Error Response Conventions

| Status | Meaning                                    |
|--------|--------------------------------------------|
| 200    | Success                                    |
| 201    | Created                                    |
| 400    | Bad Request                                |
| 401    | Unauthorized (missing/invalid JWT)         |
| 402    | Payment Required (payment failed)          |
| 403    | Forbidden (internal endpoint, wrong tenant)|
| 404    | Not Found                                  |
| 409    | Conflict (invalid state transition)        |
| 422    | Unprocessable Entity (validation errors)   |
| 429    | Too Many Requests (rate limit)             |
| 500    | Internal Server Error                      |
| 502    | Bad Gateway (upstream service unavailable) |

## Common Headers

| Header              | Direction     | Purpose                                      |
|---------------------|---------------|----------------------------------------------|
| `Authorization`     | Client → GW   | JWT Bearer token                             |
| `X-Tenant-ID`       | GW → Services | Tenant identifier extracted from JWT         |
| `X-User-ID`         | GW → Services | User subject extracted from JWT              |
| `X-User-Role`       | GW → Services | User role extracted from JWT                 |
| `X-Correlation-ID`  | GW → Services | Request tracing ID                           |
| `X-Forwarded-By`    | GW → Services | Identifies the gateway                       |
| `X-Internal-Service`| Svc → Svc     | Authenticates internal service-to-service    |
