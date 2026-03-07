"# Multi-Tenant SaaS Inventory Management System

A **production-grade, multi-tenant SaaS Inventory Management System** built with a **Laravel 11 backend** and **React 18 frontend**, implementing a **modular Controller → Service → Repository architecture**, **Laravel Passport SSO (OAuth2)**, **RBAC/ABAC authorization**, **event-driven communication via RabbitMQ**, and **Saga pattern** for distributed transaction management.

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Quick Start (Docker)](#quick-start-docker)
- [Backend Setup (Local)](#backend-setup-local)
- [Frontend Setup (Local)](#frontend-setup-local)
- [API Documentation](#api-documentation)
- [Authentication & SSO](#authentication--sso)
- [Multi-Tenancy](#multi-tenancy)
- [RBAC / ABAC Authorization](#rbac--abac-authorization)
- [Event-Driven Communication](#event-driven-communication)
- [Saga Pattern](#saga-pattern)
- [Module Structure](#module-structure)
- [Testing](#testing)
- [Environment Variables](#environment-variables)

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                  React Frontend (Vite)               │
│   Auth Pages  │  Dashboard  │  Users/Products/etc   │
│          OAuth2 (Laravel Passport SSO)               │
└────────────────────────┬────────────────────────────┘
                         │  HTTPS / REST API
┌────────────────────────▼────────────────────────────┐
│             Laravel 11 Backend (Modular)             │
│                                                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐   │
│  │   Auth   │  │  Tenant  │  │ Health Check API  │   │
│  │  Module  │  │Middleware│  └──────────────────┘   │
│  └──────────┘  └──────────┘                         │
│                                                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │  User    │  │ Product  │  │Inventory │  Order    │
│  │ Module   │  │ Module   │  │ Module   │  Module   │
│  │ CRUD     │  │ CRUD     │  │ CRUD     │  + Saga   │
│  │ RBAC/ABAC│  │ Events   │  │ Events   │           │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘           │
│       │             │             │                   │
│  Controller → Service → Repository → Eloquent Model  │
└───────┼─────────────┼─────────────┼───────────────┘
        │             │             │
┌───────▼─────────────▼─────────────▼────────────────┐
│             Infrastructure Layer                     │
│  MySQL 8  │  Redis 7  │  RabbitMQ 3.13             │
└────────────────────────────────────────────────────┘
```

### Design Patterns
| Pattern | Implementation |
|---------|---------------|
| **Controller → Service → Repository** | Each module: Controller (HTTP), Service (business logic), Repository (data access) |
| **Repository Interface + DI** | `UserRepositoryInterface` → `UserRepository`, bound in `AppServiceProvider` |
| **Multi-Tenancy** | `BelongsToTenant` trait adds Eloquent global scope; `TenantMiddleware` resolves from `X-Tenant-ID` header |
| **RBAC** | `spatie/laravel-permission` roles: `super_admin`, `admin`, `manager`, `staff` |
| **ABAC** | `CheckAbacPermission` middleware inspects `abac_attributes` JSON on the user model |
| **Event-Driven** | Laravel Events + Listeners → `RabbitMQService` publishes to `inventory.exchange` topic exchange |
| **Saga** | `OrderSagaService` — validate → reserve inventory → create order → confirm, with compensating rollbacks |
| **API Resources + DTOs** | Consistent JSON responses via `*Resource` classes; `*DTO` objects for data transfer |

---

## Technology Stack

### Backend
- **PHP 8.2** + **Laravel 11**
- **Laravel Passport** — OAuth2 / SSO
- **Spatie Laravel Permission** — RBAC
- **php-amqplib** — RabbitMQ integration
- **MySQL 8** — primary database
- **Redis 7** — caching & queues
- **PHPUnit 11** — testing

### Frontend
- **React 18** + **Vite 5**
- **React Router v6** — client-side routing
- **TanStack Query v5** — server state management
- **Zustand** — auth state (persisted)
- **React Hook Form** + **Zod** — form validation
- **Axios** — HTTP client with automatic token injection
- **Tailwind CSS** — utility-first styling
- **Lucide React** — icons

### Infrastructure
- **Docker Compose** — full stack orchestration
- **Nginx** — reverse proxy
- **RabbitMQ 3.13** — message broker (management UI on `:15672`)

---

## Project Structure

```
├── backend/                          # Laravel 11 application
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── HealthCheckController.php
│   │   │   └── Middleware/
│   │   │       ├── TenantMiddleware.php
│   │   │       └── CheckAbacPermission.php
│   │   ├── Modules/
│   │   │   ├── Auth/                 # SSO / Passport
│   │   │   │   ├── Controllers/
│   │   │   │   ├── Services/
│   │   │   │   ├── Requests/
│   │   │   │   ├── Resources/
│   │   │   │   └── Routes/
│   │   │   ├── User/                 # CRUD + RBAC/ABAC
│   │   │   │   ├── Controllers/
│   │   │   │   ├── Services/
│   │   │   │   ├── Repositories/
│   │   │   │   ├── Models/
│   │   │   │   ├── Requests/
│   │   │   │   ├── Resources/
│   │   │   │   ├── DTOs/
│   │   │   │   ├── Events/
│   │   │   │   ├── Listeners/
│   │   │   │   ├── Webhooks/
│   │   │   │   ├── Routes/
│   │   │   │   └── Tests/
│   │   │   ├── Product/              # CRUD + RabbitMQ events
│   │   │   ├── Inventory/            # CRUD + cross-service
│   │   │   ├── Order/                # CRUD + Saga pattern
│   │   │   └── Tenant/               # Tenant model
│   │   ├── Services/
│   │   │   ├── HealthCheckService.php
│   │   │   └── MessageBroker/
│   │   │       ├── MessageBrokerInterface.php
│   │   │       └── RabbitMQService.php
│   │   ├── Traits/
│   │   │   └── BelongsToTenant.php
│   │   └── Providers/
│   │       ├── AppServiceProvider.php    # Repository bindings
│   │       ├── AuthServiceProvider.php   # Passport policies
│   │       ├── EventServiceProvider.php  # Event/Listener map
│   │       └── ModuleServiceProvider.php # Module route loader
│   ├── database/
│   │   ├── migrations/               # 7 migration files
│   │   ├── factories/                # 5 model factories
│   │   └── seeders/                  # Demo data seeders
│   ├── config/
│   └── Dockerfile
├── frontend/                         # React 18 application
│   ├── src/
│   │   ├── api/                      # API service layer
│   │   ├── store/                    # Zustand auth store
│   │   ├── hooks/                    # useAuth, useTenant, useDebounce
│   │   ├── context/                  # TenantContext
│   │   ├── components/
│   │   │   ├── Layout/               # Sidebar, Header, ProtectedRoute
│   │   │   └── UI/                   # Button, Table, Modal, Badge, etc.
│   │   └── pages/
│   │       ├── Auth/                 # Login, Register
│   │       ├── Dashboard/
│   │       ├── Users/
│   │       ├── Products/
│   │       ├── Inventory/
│   │       ├── Orders/
│   │       └── Health/
│   └── Dockerfile
├── docker/
│   └── nginx/
│       └── default.conf
├── docker-compose.yml
└── .env.example
```

---

## Quick Start (Docker)

```bash
# 1. Clone and enter directory
git clone https://github.com/kasunvimarshana/MultiTenent_SAAS_SSO.git
cd MultiTenent_SAAS_SSO

# 2. Copy and configure root environment
cp .env.example .env
# Edit .env with your secrets

# 3. Start all services
docker compose up -d --build

# 4. Run backend setup (first time only)
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate
docker compose exec backend php artisan passport:install
docker compose exec backend php artisan db:seed

# 5. Access the app
# Frontend:        http://localhost:3000
# Backend API:     http://localhost:8000/api/v1
# Health Check:    http://localhost:8000/api/health
# RabbitMQ UI:     http://localhost:15672  (admin/admin_password)
```

---

## Backend Setup (Local)

```bash
cd backend
composer install
cp .env.example .env

# Configure database, Redis, RabbitMQ in .env

php artisan key:generate
php artisan migrate
php artisan passport:install
php artisan db:seed

# Start server
php artisan serve

# Start queue worker
php artisan queue:work
```

---

## Frontend Setup (Local)

```bash
cd frontend
npm install
cp .env.example .env

# Set VITE_API_BASE_URL=http://localhost:8000

npm run dev
# App available at http://localhost:3000
```

---

## API Documentation

All API endpoints are prefixed with `/api/v1`. Protected routes require:
- `Authorization: Bearer <access_token>`
- `X-Tenant-ID: <tenant_id>`

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/register` | Register new user |
| POST | `/api/v1/auth/login` | Login, returns OAuth2 token |
| POST | `/api/v1/auth/logout` | Revoke token |
| POST | `/api/v1/auth/refresh` | Refresh access token |
| GET | `/api/v1/auth/me` | Get current user |

### Users (RBAC protected)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/users` | List users (paginated, filterable) |
| POST | `/api/v1/users` | Create user |
| GET | `/api/v1/users/{id}` | Get user |
| PUT | `/api/v1/users/{id}` | Update user |
| DELETE | `/api/v1/users/{id}` | Delete user |

### Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products` | List products |
| POST | `/api/v1/products` | Create product → fires `ProductCreated` event |
| GET | `/api/v1/products/{id}` | Get product |
| PUT | `/api/v1/products/{id}` | Update product → fires `ProductUpdated` event |
| DELETE | `/api/v1/products/{id}` | Delete product → fires `ProductDeleted` event |
| POST | `/api/v1/products/webhook` | Receive product webhook |

### Inventory

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/inventory` | List inventory items |
| POST | `/api/v1/inventory` | Create inventory record |
| GET | `/api/v1/inventory/{id}` | Get inventory item |
| PUT | `/api/v1/inventory/{id}` | Update inventory |
| DELETE | `/api/v1/inventory/{id}` | Delete inventory record |
| POST | `/api/v1/inventory/{id}/adjust` | Adjust stock quantity |

### Orders (Saga pattern)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/orders` | List orders |
| POST | `/api/v1/orders` | Create order (Saga: validate → reserve → confirm) |
| GET | `/api/v1/orders/{id}` | Get order with items |
| PUT | `/api/v1/orders/{id}` | Update order |
| POST | `/api/v1/orders/{id}/cancel` | Cancel order (compensating transaction) |

### Query Parameters (all list endpoints)

| Parameter | Example | Description |
|-----------|---------|-------------|
| `per_page` | `?per_page=25` | Items per page (default: 15) |
| `page` | `?page=2` | Page number |
| `search` | `?search=widget` | Full-text search |
| `filter[field]` | `?filter[category]=electronics` | Field filter |
| `sort_by` | `?sort_by=name` | Sort field |
| `sort_dir` | `?sort_dir=asc` | Sort direction (`asc`/`desc`) |

### Health Check

```bash
GET /api/health
# Returns status of: database, redis, rabbitmq
```

---

## Authentication & SSO

The system uses **Laravel Passport** (OAuth2 Authorization Server) for SSO:

1. Users authenticate via `POST /api/v1/auth/login` with email, password, and `tenant_id`
2. The server returns an **access token** and **refresh token**
3. The React frontend stores tokens in `localStorage` via Zustand persist
4. Every subsequent request includes `Authorization: Bearer <token>` and `X-Tenant-ID: <id>`
5. The Axios interceptor handles **silent token refresh** on 401 responses
6. All Laravel microservices validate the Passport-issued token via `auth:api` middleware

---

## Multi-Tenancy

Each resource is scoped to a tenant:

- **`BelongsToTenant` trait** automatically adds a `WHERE tenant_id = ?` global scope to all Eloquent queries
- **`TenantMiddleware`** resolves the tenant from `X-Tenant-ID` header and stores it in the DI container
- Models automatically inherit `tenant_id` on creation when the tenant context is set

---

## RBAC / ABAC Authorization

### RBAC (Role-Based)
Built on `spatie/laravel-permission`. Available roles:
- `super_admin` — Full access across all tenants
- `admin` — Full access within tenant
- `manager` — Manage products and inventory
- `staff` — Read-only + basic operations

### ABAC (Attribute-Based)
The `CheckAbacPermission` middleware checks the user's `abac_attributes` JSON field:
```json
{
  "department": "warehouse",
  "can_approve_orders": true,
  "max_order_value": 10000
}
```
Use `->middleware('abac:can_approve_orders')` on route definitions.

---

## Event-Driven Communication

Domain events are dispatched via Laravel Events and published to **RabbitMQ**:

| Event | Routing Key | Payload |
|-------|-------------|---------|
| `ProductCreated` | `product.created` | `{product_id, tenant_id, name, sku}` |
| `ProductUpdated` | `product.updated` | `{product_id, tenant_id, name, sku}` |
| `ProductDeleted` | `product.deleted` | `{product_id, tenant_id}` |
| `InventoryUpdated` | `inventory.updated` | `{inventory_id, product_id, quantity}` |
| `OrderCreated` | `order.created` | `{order_id, tenant_id, total}` |
| `OrderCancelled` | `order.cancelled` | `{order_id, tenant_id}` |

All events are published to the `inventory.exchange` topic exchange. Listeners implement `ShouldQueue` for async processing.

---

## Saga Pattern

The `OrderSagaService` implements a **choreography-based Saga** for distributed order creation:

```
1. Validate inventory availability for all items
2. Enrich items with current prices from Product service
3. Reserve inventory (deduct quantities)
4. Create order record with items
5. Mark order as 'confirmed'
6. Commit transaction

On any failure:
  - Roll back DB transaction
  - Execute compensating transaction: release reserved inventory
  - Log critical errors for monitoring
```

---

## Module Structure

Each module follows a consistent internal structure:

```
Modules/{ModuleName}/
├── Controllers/     # HTTP request/response handling
├── Services/        # Business logic orchestration
├── Repositories/    # Data access via Eloquent (interface + implementation)
├── Models/          # Eloquent models
├── Requests/        # Form Request validation
├── Resources/       # API Resource transformers
├── DTOs/            # Data Transfer Objects
├── Events/          # Domain events
├── Listeners/       # Event handlers (ShouldQueue)
├── Webhooks/        # Inbound webhook handlers
├── Routes/          # Module-scoped API routes
└── Tests/           # PHPUnit feature tests
```

---

## Testing

```bash
cd backend

# Run all tests
php artisan test

# Run specific module tests
php artisan test --filter UserTest
php artisan test --filter ProductTest
php artisan test --filter InventoryTest
php artisan test --filter OrderTest

# With coverage
php artisan test --coverage
```

Test coverage includes:
- CRUD operations for all modules
- Authentication and authorization
- Tenant isolation (cross-tenant access prevention)
- Pagination, filtering, sorting
- Saga success and failure paths

---

## Environment Variables

### Backend (`backend/.env`)

```env
APP_NAME="SaaS Inventory"
APP_ENV=local
APP_KEY=                           # php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=saas_inventory
DB_USERNAME=root
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/

CACHE_STORE=redis
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

### Frontend (`frontend/.env`)

```env
VITE_API_BASE_URL=http://localhost:8000
VITE_PASSPORT_CLIENT_ID=2
VITE_PASSPORT_CLIENT_SECRET=your-client-secret
VITE_APP_NAME=SaaS Inventory
```

---

## Demo Credentials (after seeding)

| Role | Email | Password | Tenant ID |
|------|-------|----------|-----------|
| Super Admin | superadmin@example.com | password | 1 |
| Admin | admin@tenant1.com | password | 1 |
| Manager | manager@tenant1.com | password | 1 |
| Staff | staff@tenant1.com | password | 1 |

---

## License

MIT" 
