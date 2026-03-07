# SaaS Multi-Tenant Inventory Management System with Saga Pattern

A production-grade microservices architecture demonstrating distributed transactions using the **Saga Orchestration Pattern**, multi-tenant isolation, RBAC/ABAC authorization, and polyglot persistence.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        Client (React + TypeScript)               │
└─────────────────────────────┬───────────────────────────────────┘
                              │ HTTP
┌─────────────────────────────▼───────────────────────────────────┐
│                    API Gateway (Laravel 10)                       │
│          Rate limiting · Request routing · Tracing               │
└────┬──────────────┬──────────────┬──────────────┬───────────────┘
     │              │              │              │
┌────▼────┐  ┌──────▼──────┐ ┌────▼────┐  ┌────▼─────────────┐
│  Auth   │  │  Inventory  │ │  Order  │  │  Notification    │
│ Service │  │  Service    │ │ Service │  │  Service         │
│Laravel  │  │  Laravel    │ │Laravel  │  │  Node.js         │
│MySQL    │  │  PostgreSQL │ │MySQL    │  │  MongoDB         │
│Passport │  │             │ │  SAGA   │  │  RabbitMQ        │
└────┬────┘  └──────┬──────┘ └────┬────┘  └──────────────────┘
     │              │              │
     └──────────────┴──────────────┘
                    │
          ┌─────────▼─────────┐
          │     RabbitMQ      │
          │   (Message Bus)   │
          └───────────────────┘
```

---

## Saga Orchestration Pattern

The **Order Service** contains the Saga orchestrator. When an order is created, it executes 4 steps in sequence. If any step fails, compensating transactions run in **LIFO order** to roll back the distributed transaction.

```
CREATE ORDER
     │
     ▼
┌─────────────────────────────────────────────┐
│             Saga Orchestrator               │
│                                             │
│  Step 1: ReserveInventory  ──────────────►  │  Inventory Service
│          ↳ compensate: ReleaseInventory     │  (PostgreSQL)
│                                             │
│  Step 2: ProcessPayment    ──────────────►  │  Payment Gateway
│          ↳ compensate: RefundPayment        │  (local simulation)
│                                             │
│  Step 3: ConfirmOrder      ──────────────►  │  Order DB
│          ↳ compensate: CancelOrder          │  (MySQL)
│                                             │
│  Step 4: SendNotification  ──────────────►  │  Notification Service
│          ↳ compensate: SendCancellation     │  (Node.js / MongoDB)
│                                             │
└─────────────────────────────────────────────┘
```

All step state transitions are persisted to the `saga_logs` table for **observability** and **crash recovery**.

---

## Services

| Service | Language | Framework | Database | Port |
|---|---|---|---|---|
| **api-gateway** | PHP 8.2 | Laravel 10 | Redis (cache) | 8000 |
| **auth-service** | PHP 8.2 | Laravel 10 + Passport | MySQL 8 | 8001 |
| **inventory-service** | PHP 8.2 | Laravel 10 | PostgreSQL 15 | 8002 |
| **order-service** | PHP 8.2 | Laravel 10 | MySQL 8 | 8003 |
| **notification-service** | Node.js 20 | Express 4 | MongoDB 7 | 8004 |
| **frontend** | TypeScript | React 18 + Vite | – | 3000 |

---

## Key Design Principles

### 1. Interface-Driven Design
Every service layer is backed by an interface:
- `AuthServiceInterface` / `TenantServiceInterface` / `AuthorizationServiceInterface`
- `InventoryRepositoryInterface` / `ProductServiceInterface`
- `OrderServiceInterface` / `SagaOrchestratorInterface` / `SagaStepInterface`
- `GatewayProxyInterface`
- `NotificationServiceContract` (Node.js)

This allows any implementation to be swapped without breaking consuming code.

### 2. Multi-Tenant Isolation
- Every database record carries a `tenant_id`
- `TenantMiddleware` resolves the current tenant from `X-Tenant-ID` header
- ABAC policies enforce cross-tenant access prevention

### 3. RBAC + ABAC Authorization
- **RBAC**: Spatie Permission with tenant-scoped roles (`admin:{tenantId}`, `warehouse_manager:{tenantId}`)
- **ABAC**: User attributes (department, clearance) evaluated against resource attributes

### 4. Saga Compensating Transactions
- Each `SagaStep` has `execute()` + `compensate()` methods
- Compensations are **idempotent** (safe to call multiple times)
- `SagaOrchestrator` runs compensations in **LIFO order** on failure
- Every state transition is logged to `saga_logs` table

---

## Quick Start

### Prerequisites
- Docker 24+
- Docker Compose v2+
- Make

### 1. Clone and setup
```bash
git clone <repository-url>
cd SAAS_MultiTenent_SAGA
make setup
```

### 2. Start all services
```bash
make up
```

### 3. Run migrations and seeders
```bash
make migrate
make seed
```

### 4. Access the application
- **Frontend**: http://localhost:3000
- **API Gateway**: http://localhost:8000
- **RabbitMQ Management**: http://localhost:15672 (admin/secret)
- **Auth Service**: http://localhost:8001
- **Inventory Service**: http://localhost:8002
- **Order Service**: http://localhost:8003
- **Notification Service**: http://localhost:8004

### Default credentials
| Email | Password | Role |
|---|---|---|
| `superadmin@saas.local` | `password` | Super Admin |
| `admin@default.local` | `password` | Tenant Admin |

---

## API Examples

### Authentication
```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: <tenant-uuid>" \
  -d '{"email":"admin@default.local","password":"password"}'

# Get current user
curl http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer <token>"
```

### Create Order (triggers Saga)
```bash
curl -X POST http://localhost:8000/api/v1/orders/orders \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: <tenant-uuid>" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"product_id": "<product-uuid>", "quantity": 2, "unit_price": 29.99}
    ],
    "total_amount": 59.98,
    "currency": "USD",
    "payment_method": "credit_card",
    "user_email": "customer@example.com"
  }'
```

### Reserve Inventory (Saga step)
```bash
curl -X POST http://localhost:8000/api/v1/inventory/inventory/reserve \
  -H "Authorization: Bearer <token>" \
  -H "X-Tenant-ID: <tenant-uuid>" \
  -H "Content-Type: application/json" \
  -d '{"product_id": "<uuid>", "quantity": 2, "order_id": "<order-uuid>"}'
```

---

## Development Commands

```bash
make up           # Start all services
make down         # Stop all services
make logs         # Tail all service logs
make migrate      # Run all database migrations
make seed         # Run all database seeders
make test         # Run all tests
make shell-auth   # Shell into auth-service container
make shell-order  # Shell into order-service container
```

---

## Testing

### Laravel services (PHPUnit)
```bash
# Auth service
docker compose exec auth_service php artisan test

# Inventory service
docker compose exec inventory_service php artisan test

# Order service (Saga tests)
docker compose exec order_service php artisan test
```

### Notification service (Jest)
```bash
docker compose exec notification_service npm test
```

### Frontend (Vitest)
```bash
cd frontend && npm test
```

---

## Project Structure

```
SAAS_MultiTenent_SAGA/
├── docker-compose.yml          # Full stack orchestration
├── Makefile                    # Developer convenience commands
├── .env.example                # Root environment template
│
├── auth-service/               # PHP 8.2 + Laravel 10 + MySQL
│   ├── app/
│   │   ├── Contracts/          # AuthService, TenantService, AuthorizationService interfaces
│   │   ├── Models/             # User (HasApiTokens, HasRoles), Tenant
│   │   ├── Services/           # AuthService (Passport), TenantService, AuthorizationService (RBAC+ABAC)
│   │   └── Http/               # Controllers, Middleware, Requests
│   └── database/migrations/
│
├── inventory-service/          # PHP 8.2 + Laravel 10 + PostgreSQL
│   ├── app/
│   │   ├── Contracts/          # InventoryRepository, ProductService interfaces
│   │   ├── Models/             # Product, InventoryItem
│   │   ├── Repositories/       # InventoryRepository (SELECT FOR UPDATE)
│   │   └── Services/           # ProductService
│   └── database/migrations/
│
├── order-service/              # PHP 8.2 + Laravel 10 + MySQL + SAGA
│   ├── app/
│   │   ├── Contracts/          # SagaStep, SagaOrchestrator, OrderService interfaces
│   │   ├── Saga/
│   │   │   ├── SagaContext.php         # Shared data bag
│   │   │   ├── SagaOrchestrator.php    # LIFO compensation engine
│   │   │   └── Steps/
│   │   │       ├── ReserveInventoryStep.php
│   │   │       ├── ProcessPaymentStep.php
│   │   │       ├── ConfirmOrderStep.php
│   │   │       └── SendNotificationStep.php
│   │   └── Models/             # Order, OrderItem, Payment, SagaLog
│   └── database/migrations/
│
├── notification-service/       # Node.js 20 + Express + MongoDB (polyglot)
│   ├── src/
│   │   ├── contracts/          # NotificationServiceContract (abstract base)
│   │   ├── services/           # EmailNotificationService, NotificationProcessor
│   │   └── messaging/          # RabbitMQ consumer (choreography complement)
│   └── tests/
│
├── api-gateway/                # PHP 8.2 + Laravel 10
│   └── app/
│       ├── Contracts/          # GatewayProxyInterface
│       └── Services/           # GatewayProxy (Guzzle + X-Request-ID tracing)
│
└── frontend/                   # React 18 + TypeScript + Vite
    └── src/
        ├── services/           # apiClient, authService, inventoryService, orderService
        ├── store/              # Zustand authStore
        └── pages/              # Login, Dashboard, Products, Inventory, Orders (Saga log viewer)
```

---

## Security Considerations

- All database passwords and secrets must be changed for production deployments
- Laravel Passport keys should be generated fresh per environment
- The API Gateway enforces rate limiting (60 req/min per IP by default)
- Multi-tenant isolation is enforced at both middleware and ABAC policy levels
- Saga compensations are idempotent to handle duplicate execution safely
- Stock reservation uses `SELECT FOR UPDATE` to prevent race conditions

---

## License

MIT
