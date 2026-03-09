# KV Laravel SaaS Multi-Tenant SSO Microservice Platform

An enterprise-grade, microservice-driven **Inventory Management System** built with a multi-tenant SaaS architecture. Each microservice is independently deployable and can use its own technology stack.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         API Gateway                              │
│                    (Node.js / Express)                           │
│     Rate Limiting · CORS · Auth Proxy · Service Routing          │
└────────────────┬─────────────────────────────────┬──────────────┘
                 │                                 │
    ┌────────────▼──────────┐         ┌───────────▼────────────┐
    │     Auth Service       │         │   Inventory Service     │
    │  (Laravel 11 + Passport│         │   (Laravel 11 + DDD)    │
    │  Multi-tenant SSO      │         │   Base Repository       │
    │  RBAC + ABAC           │         │   Saga Stock Reserve    │
    └───────────────────────┘         └────────────────────────┘
    ┌──────────────────────┐          ┌────────────────────────┐
    │    Order Service      │          │  Notification Service  │
    │ (Laravel 11 + Saga)   │          │  (Laravel 11)          │
    │ Distributed Transactions│         │  Webhooks + Email      │
    │ Rollback Compensation │          │  HMAC Signing          │
    └──────────────────────┘          └────────────────────────┘
    ┌──────────────────────────────────────────────────────────┐
    │                    Saga Orchestrator                      │
    │              (Laravel 11 - Coordination)                  │
    │         Distributed Transaction State Management          │
    └──────────────────────────────────────────────────────────┘
                 │                                 │
    ┌────────────▼──────────┐         ┌───────────▼────────────┐
    │       RabbitMQ         │         │         Kafka          │
    │   (Message Broker)     │         │   (Stream Processing)  │
    └───────────────────────┘         └────────────────────────┘
                 │
    ┌────────────▼──────────────────────────────┐
    │              Redis (Cache + Sessions)       │
    └────────────────────────────────────────────┘
    ┌──────────┐  ┌──────────┐  ┌──────────────┐
    │  MySQL   │  │  MySQL   │  │    MySQL     │
    │ (Auth)   │  │(Inventory)│  │  (Orders)   │
    └──────────┘  └──────────┘  └─────────────┘
```

## Key Features

- 🏢 **Multi-Tenant SaaS** — Strict tenant isolation with hierarchical organization support
- 🔐 **Laravel Passport SSO** — OAuth2-based stateless multi-guard authentication
- 🛡️ **RBAC + ABAC** — Role-Based and Attribute-Based Access Control per tenant
- 🔄 **Saga Pattern** — Distributed transactions with automatic rollback compensation
- 📨 **Pluggable Message Broker** — RabbitMQ, Kafka, or Log (dev) via strategy pattern
- 📦 **DDD Architecture** — Domain-Driven Design with strict layered architecture
- 🏗️ **Base Repository** — Fully dynamic with conditional pagination, filtering, sorting
- 🔗 **Webhook Integration** — HMAC-signed webhooks with exponential backoff retry
- 💚 **Health Check Endpoints** — `/health`, `/health/live`, `/health/ready` on all services
- 🚀 **API Gateway** — Rate limiting, CORS, centralized auth, service proxy

## Microservices

| Service | Technology | Port | Database |
|---------|-----------|------|----------|
| API Gateway | Node.js / Express | 8000 | — |
| Auth Service | Laravel 11 + Passport | 8001 | MySQL (auth_db) |
| Inventory Service | Laravel 11 | 8002 | MySQL (inventory_db) |
| Order Service | Laravel 11 | 8003 | MySQL (order_db) |
| Notification Service | Laravel 11 | 8004 | MySQL (notification_db) |
| Saga Orchestrator | Laravel 11 | 8005 | MySQL (saga_db) |

## Quick Start

### Prerequisites
- Docker 24+
- Docker Compose v2+
- Make

### Start All Services

```bash
# Clone the repository
git clone <repo-url>
cd KV_Laravel_SAAS_MultiTenent_SSO_MicoService

# Copy environment files
cp services/auth-service/.env.example services/auth-service/.env
cp services/inventory-service/.env.example services/inventory-service/.env
cp services/order-service/.env.example services/order-service/.env
cp services/notification-service/.env.example services/notification-service/.env
cp services/saga-orchestrator/.env.example services/saga-orchestrator/.env
cp services/api-gateway/.env.example services/api-gateway/.env

# Start all services
make up

# Run migrations and setup Passport
make migrate

# Generate Passport keys (inside auth service container)
docker-compose exec auth-service php artisan passport:install

# Check health
make health
```

### API Endpoints

#### Authentication (via API Gateway on port 8000)

```bash
# Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"tenant_id":1,"name":"John Doe","email":"john@example.com","password":"secret123","password_confirmation":"secret123"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"secret123"}'

# Get profile
curl http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer <token>"
```

#### Inventory

```bash
# List products (with pagination)
curl http://localhost:8000/api/products?per_page=10&page=1 \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: 1"

# Create product
curl -X POST http://localhost:8000/api/products \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: 1" \
  -H "Content-Type: application/json" \
  -d '{"sku":"PROD-001","name":"Widget","price":29.99,"quantity":100}'
```

#### Orders (with Saga)

```bash
# Create order (triggers Saga: reserve stock → payment → notification)
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: 1" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"product_id":1,"quantity":2,"unit_price":29.99}
    ],
    "shipping_address": {"street":"123 Main St","city":"NYC","country":"US"}
  }'

# Cancel order (triggers compensation rollback)
curl -X POST http://localhost:8000/api/orders/1/cancel \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: 1"
```

#### Webhooks

```bash
# Register webhook
curl -X POST http://localhost:8000/api/webhooks \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: 1" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://your-app.com/webhook","events":["order.created","stock.low"],"secret":"your-secret"}'
```

## Architecture Deep Dive

### Saga Pattern — Distributed Transaction Flow

```
Client → API Gateway → Order Service
                          │
                          ▼ Step 1: Create Order (local)
                          │
                          ▼ Step 2: Reserve Stock (→ Inventory Service HTTP)
                          │
                          ▼ Step 3: Process Payment (→ Payment Service stub)
                          │
                          ▼ Step 4: Send Notification (→ Message Broker)
                          │
                          ▼ Step 5: Confirm Stock Deduction
                          │
                     [SUCCESS] Order status → confirmed
                          
If Step 3 fails:
  Compensation in reverse:
    ← Compensate Step 2: Release stock reservation
    ← Compensate Step 1: Mark order as failed
  Order status → failed
```

### Base Repository — Conditional Pagination

The `BaseRepository` returns different types based on input:

```php
// Returns Collection (all results)
$products = $repository->all(['category_id' => 1]);

// Returns LengthAwarePaginator (paginated)
$products = $repository->all(['category_id' => 1, 'per_page' => 10, 'page' => 2]);

// Paginate any iterable (arrays, API responses, collections)
$paginated = $repository->paginateIterable($externalApiData, perPage: 15, page: 1);
```

### Multi-Tenant Architecture

Each request carries tenant context via `X-Tenant-ID` header. The `TenantMiddleware` resolves and binds the tenant to all downstream queries. Runtime tenant configuration is supported — change cache drivers, DB connections, email settings per-tenant without restart.

### Message Broker — Strategy Pattern

Switch brokers at runtime via `MESSAGE_BROKER` env var:

```env
MESSAGE_BROKER=rabbitmq  # Production
MESSAGE_BROKER=kafka     # Streaming workloads
MESSAGE_BROKER=log       # Development/testing
```

### RBAC + ABAC Authorization

```php
// Check role
$user->hasRole('admin');

// Check specific permission (ABAC)
$user->hasPermission('inventory.products.delete');

// Route-level permission middleware
Route::middleware('permission:inventory.products.create')
     ->post('/products', ...);
```

## Development

```bash
# Run tests
make test

# View logs
make logs

# Restart a specific service
docker-compose restart inventory-service

# Connect to inventory service shell
docker-compose exec inventory-service sh

# Run PHP artisan commands
docker-compose exec inventory-service php artisan tinker
```

## Project Structure

```
├── docker-compose.yml           # Full service mesh
├── Makefile                     # Dev/ops automation
├── shared/
│   └── contracts/               # Shared interfaces
│       ├── RepositoryInterface.php
│       ├── MessageBrokerInterface.php
│       ├── SagaInterface.php
│       └── TenantInterface.php
└── services/
    ├── api-gateway/             # Node.js gateway
    │   └── src/
    │       ├── app.js
    │       ├── routes/
    │       └── middleware/
    ├── auth-service/            # Laravel Passport SSO
    │   └── app/
    │       ├── Domain/          # Entities, contracts, events
    │       ├── Infrastructure/  # Repositories, services
    │       └── Http/            # Controllers, middleware, requests
    ├── inventory-service/       # Laravel DDD inventory
    │   └── app/
    │       ├── Domain/
    │       ├── Infrastructure/
    │       │   ├── Database/Repositories/
    │       │   │   ├── BaseRepository.php  # Core reusable repo
    │       │   │   └── ProductRepository.php
    │       │   ├── Messaging/             # Broker abstraction
    │       │   └── Services/
    │       └── Http/
    ├── order-service/           # Saga Pattern orders
    │   └── app/
    │       ├── Infrastructure/
    │       │   └── Saga/
    │       │       └── CreateOrderSaga.php
    │       └── ...
    ├── notification-service/    # Webhooks + notifications
    └── saga-orchestrator/       # Distributed tx coordinator
```

## Security

- Bearer token authentication validated per-request against Auth Service
- HMAC SHA-256 signed webhooks (timing-safe comparison with `hash_equals`)
- Tenant isolation enforced at middleware + query levels
- Rate limiting on API Gateway (configurable)
- Helmet.js security headers on API Gateway
- No secrets committed — all via `.env` files
- SQL injection prevented via Eloquent ORM + sort column whitelisting

## License

MIT 
