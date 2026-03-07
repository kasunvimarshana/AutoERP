"# KV Multi-Tenant SaaS Inventory Management System

An enterprise-grade, fully dynamic, extensible, and customizable **multi-tenant SaaS Inventory Management System** with a React frontend and Laravel backend.

---

## Architecture Overview

```
frontend/          React 18 + TypeScript + TailwindCSS
backend/           Laravel 11 (PHP 8.1+)
  ├── app/
  │   ├── Http/Controllers/   BaseController → module controllers
  │   ├── Services/           BaseService → module services
  │   ├── Repositories/       BaseRepository → module repositories
  │   ├── Models/             Eloquent models (all tenant-scoped)
  │   ├── Saga/               SagaOrchestrator + Steps (distributed transactions)
  │   ├── MessageBroker/      MessageBrokerInterface + Sync/Redis drivers
  │   ├── Events/ Listeners/  Event-driven communication
  │   ├── Http/Middleware/    Tenant, RBAC, ABAC middleware
  │   ├── Policies/           ABAC policies
  │   └── Jobs/               Async webhook delivery
  ├── database/migrations/    11 migration files
  ├── database/seeders/       Tenant, roles & permissions seeders
  ├── routes/api.php          REST API routes
  └── config/                 tenant.php, webhook.php, saga.php
```

---

## Features

### Backend
| Feature | Implementation |
|---|---|
| **Architecture** | Controller → Service → Repository per module |
| **Base Repository** | CRUD, range/operator/array filters, full-text search (including relations), relation sort, bulk ops, ACID `transaction()` |
| **Auth (SSO)** | Laravel Passport — login, register, logout, refresh, token introspect |
| **RBAC** | `CheckPermission` middleware, 5 roles (superadmin, admin, manager, user, viewer), 36 permissions |
| **ABAC** | `CheckAbacPolicy` middleware, `BasePolicy` with tenant ownership check |
| **Multi-Tenancy** | `TenantMiddleware` (header/subdomain/domain), `BelongsToTenant` global scope trait, runtime config per tenant |
| **MessageBroker** | `MessageBrokerInterface` → `SyncMessageBroker` + `RedisMessageBroker` + `MessageBrokerManager` |
| **Saga Pattern** | `SagaOrchestrator` with reverse compensation: CreateOrder → ReserveInventory → ProcessPayment |
| **ACID Inventory** | `lockForUpdate()` in adjust/reserve with `InventoryTransaction` audit records |
| **Webhooks** | HMAC-signed `DeliverWebhookJob`, `WebhookService`, auto-disable on persistent failure |
| **Health Checks** | `/api/health`, `/api/health/ping`, `/api/health/ready`, `/api/health/live` |
| **Modules** | User, Product, Inventory, Order — each with controller/service/repository/model/migration/seeder |

### Frontend
- React 18 + TypeScript + TailwindCSS
- TanStack Query (React Query) for data fetching with auto-refresh
- JWT token management with 401 auto-refresh interceptor
- Multi-tenant context (X-Tenant-ID header injection)
- RBAC-aware navigation (sidebar items hidden based on permissions)
- Pages: Dashboard (charts), Users, Products, Inventory (adjust + transaction history), Orders, Webhooks, Settings, Health

---

## Quick Start

### Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
# Configure DB credentials in .env, then:
php artisan migrate --seed
php artisan passport:install
php artisan serve
```

### Frontend

```bash
cd frontend
cp .env.example .env
# Set REACT_APP_API_URL=http://localhost:8000
npm install
npm start
```

### Default Credentials (after seeding)
| Role | Email | Password |
|---|---|---|
| Superadmin | superadmin@example.com | password |
| Admin | admin@example.com | password |

Include header `X-Tenant-ID: 1` on all API requests (or use `?tenant_id=1` in dev).

---

## API Reference

### Auth
```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/refresh
GET    /api/v1/auth/me
GET    /api/v1/auth/introspect
```

### Products
```
GET    /api/v1/products?search=&sort_by=name&sort_direction=asc&per_page=15&filters[status]=active
POST   /api/v1/products
GET    /api/v1/products/{id}
PUT    /api/v1/products/{id}
DELETE /api/v1/products/{id}
```

### Inventory
```
GET    /api/v1/inventory
POST   /api/v1/inventory/stock-in
POST   /api/v1/inventory/stock-out
POST   /api/v1/inventory/adjust
GET    /api/v1/inventory/low-stock
GET    /api/v1/inventory/out-of-stock
```

### Orders (Saga)
```
POST   /api/v1/orders          # Triggers Saga: CreateOrder → ReserveInventory → ProcessPayment
GET    /api/v1/orders/{id}
PATCH  /api/v1/orders/{id}/status
PATCH  /api/v1/orders/{id}/cancel
```

### Health
```
GET    /api/health             # Full check (DB, cache, Redis, storage)
GET    /api/health/ping
GET    /api/health/ready
GET    /api/health/live
```

---

## Environment Variables

See `backend/.env.example` and `frontend/.env.example` for full configuration options including:
- Database, Redis, Cache, Queue settings
- Passport token expiry
- Tenant resolution mode (header/subdomain/domain)
- MessageBroker driver (sync/redis)
- Saga async mode and timeouts
- Webhook secret, retry attempts, SSL verification
" 
