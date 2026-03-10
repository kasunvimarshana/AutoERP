# KV-SAAS — Enterprise Microservice Inventory Management System

> Production-ready, enterprise-grade, multi-tenant SaaS platform built with **Laravel microservices**, **Domain-Driven Design**, **Clean Architecture**, **Saga Pattern** for distributed transactions, and **dynamic runtime tenant configuration**.

---

## Architecture Overview

```
┌──────────────────────────────────────────────────────────────────────┐
│                        Nginx API Gateway (:80)                        │
│  /api/auth/*→Auth  /api/users/*→User  /api/products/*→Product        │
│  /api/inventory/*→Inventory           /api/orders/*→Order            │
└──────────────────────────────┬───────────────────────────────────────┘
                               │ HTTP / REST
      ┌────────────────────────┼──────────────────────┐
      ▼                        ▼                       ▼
 ┌──────────┐           ┌──────────┐            ┌──────────┐
 │  Auth    │           │  User    │            │ Product  │
 │ Service  │           │ Service  │            │ Service  │
 │  :8001   │           │  :8002   │            │  :8003   │
 └──────────┘           └──────────┘            └──────────┘
      │                                               │
      │  Saga Orchestration                           │
      │                   ┌───────────────────────────┤
      ▼                   ▼                           ▼
 ┌──────────┐      ┌──────────┐                ┌──────────┐
 │Inventory │◄─────│  Order   │                │  Redis   │
 │ Service  │      │ Service  │                │  Cache / │
 │  :8004   │      │  :8005   │                │  Queue   │
 └──────────┘      └──────────┘                └──────────┘
      │                  │
      └──────────────────┘
             │
      ┌──────────┐
      │ RabbitMQ │
      │ :5672    │
      └──────────┘
```

### Design Principles

| Principle | Implementation |
|-----------|---------------|
| **DDD** | Domain → Application → Infrastructure → HTTP layers per service |
| **Clean Architecture** | Controller → Service → Repository; thin controllers |
| **SOLID / DRY / KISS** | Interfaces in Domain; concrete classes in Infrastructure |
| **Multi-tenant SaaS** | Runtime tenant resolution; per-tenant DB/cache/queue/mail |
| **Saga Pattern** | Orchestrator-based with compensating rollbacks |
| **API-first** | JSON REST APIs; Resource classes; versioned routes |
| **No hardcoded values** | All config from ENV; dynamic repository filters |

---

## Microservices

| Service | Port | Database | Purpose |
|---------|------|----------|---------|
| **auth-service** | 8001 | MySQL | Passport SSO, RBAC/ABAC, tenant management, webhooks |
| **user-service** | 8002 | MySQL | User profile management, role assignment |
| **product-service** | 8003 | MySQL | Product catalog, categories, full-text search |
| **inventory-service** | 8004 | MySQL | Stock management, reservations, adjustments |
| **order-service** | 8005 | MySQL | Order orchestration via Saga pattern |

---

## Technology Stack

- **Runtime**: PHP 8.2, Laravel 11
- **Auth**: Laravel Passport (OAuth2 / SSO)
- **Authorization**: Spatie Laravel Permission (RBAC + ABAC)
- **Databases**: MySQL 8.0 (per-service, independently scalable)
- **Cache / Queue**: Redis 7
- **Message Broker**: RabbitMQ 3.12 with topic exchanges
- **API Gateway**: Nginx 1.25
- **Containerisation**: Docker + Docker Compose

---

## Project Structure

```
KV-SAAS/
├── docker-compose.yml              # Orchestrates all services
├── .env.example                    # Environment variable template
├── infrastructure/
│   ├── nginx/                      # API gateway & proxy configs
│   ├── rabbitmq/                   # Exchange, queue, binding definitions
│   └── docker/                     # Shared php.ini, www.conf, supervisord.conf
├── shared/
│   └── contracts/                  # Shared interfaces, DTOs, Events
│       └── src/
│           ├── Interfaces/         # BaseRepositoryInterface, SagaOrchestratorInterface, etc.
│           ├── DTOs/               # PaginationDTO, TenantContextDTO
│           └── Events/             # DomainEvent base class
└── services/
    ├── auth-service/
    │   └── app/
    │       ├── Domain/             # User entity, repository interfaces, exceptions
    │       ├── Application/        # AuthService (use cases + DTOs)
    │       ├── Infrastructure/     # BaseRepository, TenantManager, EventPublisher, WebhookDispatcher
    │       └── Http/               # Thin Controllers, Form Requests, API Resources
    ├── user-service/
    ├── product-service/
    ├── inventory-service/
    └── order-service/
        └── app/
            ├── Domain/Order/Saga/  # SagaStepInterface, SagaFailedException
            ├── Infrastructure/Saga/# SagaOrchestrator + Steps (Create/Reserve/Pay/Confirm)
            └── ...
```

---

## Getting Started

### Prerequisites

- Docker ≥ 24 and Docker Compose v2

### 1 — Clone and configure

```bash
git clone https://github.com/kasunvimarshana/KV-SAAS.git
cd KV-SAAS
cp .env.example .env
# Edit .env — replace all CHANGE_ME values with real secrets
```

### 2 — Start all services

```bash
docker compose up -d --build
```

### 3 — Run migrations (each service)

```bash
docker exec kvsaas_auth_service      php artisan migrate --seed
docker exec kvsaas_user_service      php artisan migrate
docker exec kvsaas_product_service   php artisan migrate --seed
docker exec kvsaas_inventory_service php artisan migrate
docker exec kvsaas_order_service     php artisan migrate
```

### 4 — Install Passport keys

```bash
docker exec kvsaas_auth_service php artisan passport:install
```

---

## Multi-Tenant Design

Tenant configurations are stored in the `tenants.config` JSON column and cached in Redis. The `TenantManager` bootstraps every request by:

1. Resolving the tenant (header / slug / sub-domain)
2. Loading config from Redis cache (or DB on cache miss)
3. Applying database, cache, queue, and mail config at runtime **without restart**

### Tenant Resolution Priority

1. `X-Tenant-ID` header (UUID)
2. `X-Tenant-Slug` header
3. Sub-domain (`acme.api.example.com`)

### Hot-reload Config

```http
PATCH /api/tenants/{id}/config
Authorization: Bearer {admin-token}
X-Tenant-ID: {tenant-uuid}

{
  "config": {
    "cache_driver": "redis",
    "mail_host": "smtp.mailgun.org",
    "feature_flags": { "new_checkout_flow": true }
  }
}
```

---

## Authentication & Authorization

### Register

```http
POST /api/auth/register
X-Tenant-ID: {tenant-uuid}

{
  "name": "Jane Doe",
  "email": "jane@acme.com",
  "password": "SecureP@ss1",
  "password_confirmation": "SecureP@ss1",
  "tenant_id": "{tenant-uuid}",
  "role": "manager"
}
```

### Login

```http
POST /api/auth/login
X-Tenant-ID: {tenant-uuid}

{
  "email": "jane@acme.com",
  "password": "SecureP@ss1",
  "tenant_id": "{tenant-uuid}"
}
```

**Response:**
```json
{
  "data": {
    "user": {
      "id": "uuid",
      "name": "Jane Doe",
      "tenant_id": "uuid",
      "roles": ["manager"],
      "permissions": ["products:view", "inventory:adjust", "orders:create"]
    },
    "token": "eyJ0eXAiOiJKV1Qi...",
    "token_type": "Bearer",
    "expires_at": "2026-04-10T13:00:00+00:00"
  }
}
```

### RBAC / ABAC

- **RBAC**: Roles (`admin`, `manager`, `warehouse_staff`, `user`) assigned via Spatie Laravel Permission.
- **ABAC**: Fine-grained permissions use `resource:action:condition` format (e.g. `inventory:adjust:own_warehouse`).

---

## Base Repository

The `BaseRepository` is fully dynamic with zero hardcoded values. All filter operators, pagination, sorting, and relation-loading are parameterised:

```php
// Example: dynamic product listing with all features
$products = $productRepository->paginate(
    filters: [
        'tenant_id'      => $tenantId,
        'status'         => 'active',
        'price:gte'      => 10.00,
        'price:lte'      => 500.00,
        'name:like'      => 'laptop',
        'category_id:in' => ['uuid-1', 'uuid-2'],
    ],
    perPage:   20,
    columns:   ['id', 'name', 'code', 'price', 'status'],
    pageName:  'page',
    relations: ['category'],
    orderBy:   ['price' => 'asc']
);
```

**Supported filter operators:**

| Operator | SQL | Example |
|----------|-----|---------|
| `eq` (default) | `WHERE col = val` | `'status' => 'active'` |
| `like` | `WHERE col LIKE %val%` | `'name:like' => 'laptop'` |
| `not` | `WHERE col != val` | `'status:not' => 'draft'` |
| `in` | `WHERE col IN (...)` | `'id:in' => [1,2,3]` |
| `not_in` | `WHERE col NOT IN (...)` | `'id:not_in' => [4,5]` |
| `null` | `WHERE col IS NULL` | `'deleted_at:null' => true` |
| `between` | `BETWEEN min AND max` | `'price:between' => [10, 500]` |
| `gt` / `gte` / `lt` / `lte` | comparison operators | `'quantity:lte' => 5` |

---

## Saga Pattern

The Order Service uses an **Orchestrator-based Saga** for the order creation workflow:

```
POST /api/orders
      │
      ▼
 Step 1: CreateOrderStep          → Persist order (status: pending)
      │ ✓
      ▼
 Step 2: ReserveInventoryStep     → HTTP → Inventory Service (/api/inventory/reserve)
      │ ✓
      ▼
 Step 3: ProcessPaymentStep       → Charge payment processor
      │ ✓
      ▼
 Step 4: ConfirmOrderStep         → Mark confirmed; publish order.completed event
      │
      ▼
  Order confirmed ✓
```

**Failure & Compensation (reverse order):**

```
Step 3 fails (payment declined)
      │
      ▼
 ProcessPaymentStep.compensate()    → void/refund payment
 ReserveInventoryStep.compensate()  → HTTP DELETE /api/inventory/reserve/{id}
 CreateOrderStep.compensate()       → set order status = cancelled
```

Saga state persisted in Redis:

```http
GET /api/orders/saga/{sagaId}/status

{
  "data": {
    "saga_id": "550e8400-e29b-41d4-a716-446655440000",
    "status": "failed",
    "current_step": "process_payment",
    "context": { "order_id": "...", "reservation_id": "..." },
    "updated_at": "2026-03-10T13:00:00+00:00"
  }
}
```

---

## API Reference

### Auth Service (:8001)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/health` | None | Health check |
| `POST` | `/api/auth/register` | None | Register user |
| `POST` | `/api/auth/login` | None | Login + token |
| `POST` | `/api/auth/logout` | Bearer | Revoke token |
| `POST` | `/api/auth/refresh` | Bearer | Refresh token |
| `GET` | `/api/auth/me` | Bearer | Current user |
| `GET` | `/api/tenants` | Bearer+Admin | List tenants |
| `POST` | `/api/tenants` | Bearer+Admin | Create tenant |
| `PATCH` | `/api/tenants/{id}/config` | Bearer+Admin | Hot-reload config |
| `GET` | `/api/webhooks` | Bearer | List webhooks |
| `POST` | `/api/webhooks` | Bearer | Register webhook |
| `POST` | `/api/webhooks/{id}/test` | Bearer | Send test ping |
| `GET` | `/api/webhooks/{id}/deliveries` | Bearer | Delivery history |

### Product Service (:8003)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/health` | Health check |
| `GET` | `/api/products?status=active&price:gte=10&name:like=laptop` | Filtered list |
| `GET` | `/api/products/search?q=laptop` | Full-text search |
| `POST` | `/api/products` | Create product |
| `GET` | `/api/products/{id}` | Get product |
| `PUT` | `/api/products/{id}` | Update product |
| `DELETE` | `/api/products/{id}` | Soft-delete |
| `GET` | `/api/categories` | List categories |

### Inventory Service (:8004)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/health` | Health check |
| `GET` | `/api/inventory?quantity_available:lte=5` | Low stock filter |
| `GET` | `/api/inventory/product/{productId}` | Stock for a product |
| `POST` | `/api/inventory/adjust` | Adjust stock delta |
| `POST` | `/api/inventory/reserve` | Reserve stock (Saga step 2) |
| `DELETE` | `/api/inventory/reserve/{id}` | Release reservation (compensation) |

### Order Service (:8005)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/health` | Health check |
| `GET` | `/api/orders?status=confirmed` | Filtered order list |
| `POST` | `/api/orders` | Create order (Saga orchestrated) |
| `GET` | `/api/orders/{id}` | Order with line items |
| `GET` | `/api/orders/saga/{sagaId}/status` | Saga execution status |

---

## Webhook Integration

```http
POST /api/webhooks
Authorization: Bearer {token}
X-Tenant-ID: {tenant-uuid}

{
  "url": "https://your-app.example.com/webhooks/kvsaas",
  "events": ["order.completed", "order.failed", "inventory.reserved", "product.created"],
  "description": "Production webhook"
}
```

All webhook deliveries are signed with HMAC-SHA256 for verification:

```
X-Webhook-Signature: sha256=<hmac_hex>
X-Webhook-Event: order.completed
X-Webhook-Delivery-ID: <uuid>
X-Webhook-Timestamp: <unix_timestamp>
```

Verify the signature in your receiver:

```php
$signature  = $request->header('X-Webhook-Signature');
$expected   = 'sha256=' . hash_hmac('sha256', $request->getContent(), $yourSecret);
$isValid    = hash_equals($expected, $signature);
```

---

## License

MIT
