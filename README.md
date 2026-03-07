# Enterprise Multi-Tenant SaaS Inventory Management System

A fully dynamic, extendible, and reusable **multi-tenant SaaS Inventory Management System** built with a **React frontend** and a **Laravel backend**, following industrial best practices for enterprise-grade applications.

---

## 📐 Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         React Frontend (Vite + TS)                  │
│  Login · Dashboard · Users · Products · Inventory · Orders · Config │
└──────────────────────────────┬──────────────────────────────────────┘
                               │ HTTPS  (Bearer Token + X-Tenant-ID)
┌──────────────────────────────▼──────────────────────────────────────┐
│                    Laravel API (backend/)                            │
│                                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │  User    │  │ Product  │  │Inventory │  │  Order   │  Modules   │
│  │Controller│  │Controller│  │Controller│  │Controller│           │
│  │ Service  │  │ Service  │  │ Service  │  │ Service  │           │
│  │  Repo    │  │  Repo    │  │  Repo    │  │  Repo    │           │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘           │
│       │              │              │              │ OrderSaga       │
│  ┌────▼──────────────▼──────────────▼──────────────▼─────────────┐ │
│  │   TenantMiddleware · AuthorizationMiddleware (RBAC + ABAC)     │ │
│  └────────────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │   MessageBrokerInterface: NullBroker | RabbitMQ | Kafka        │ │
│  └────────────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │   PaginationHelper (per_page present → paginated, else all)    │ │
│  └────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
        │              │              │
   MySQL / SQLite    Redis        RabbitMQ / Kafka
```

---

## ✅ Implemented Requirements

| Requirement | Implementation |
|---|---|
| **Modular Microservices** | `app/Modules/{User,Product,Inventory,Order,Auth,Tenant}/` each with Controller → Service → Repository |
| **Laravel Passport + SSO** | `/api/auth/login`, `/api/auth/sso/callback`, token refresh, scoped permissions |
| **Multi-Tenancy** | `TenantMiddleware` resolves tenant from `X-Tenant-ID` header, subdomain, or `?tenant_id` |
| **RBAC + ABAC** | 5 roles (super_admin → viewer), 19 permissions, attribute-based fallback in `AuthorizationMiddleware` |
| **Tenant Runtime Config** | `TenantConfig` model + `TenantConfigService` — per-tenant mail, payment, notification settings |
| **Cross-service Queries** | Inventory filtered by product name via `whereHas('product')` |
| **Saga Pattern** | `OrderSaga` — 5 steps with compensating transactions and full rollback on failure |
| **MessageBroker Interface** | Swappable via `MESSAGE_BROKER_DRIVER=null\|rabbitmq\|kafka` |
| **Conditional Pagination** | `PaginationHelper::paginate()` — all records when no `per_page`, paginated when present |
| **Clean Architecture** | Controller → Service → Repository, interfaces, DI container bindings |
| **React Frontend** | Full CRUD UI for Users, Products, Inventory, Orders, Tenant Config with auth guards |

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.3+, Composer 2+
- Node.js 20+, npm 9+
- SQLite (default) or MySQL

### Backend Setup

```bash
cd backend

# Copy environment file
cp .env.example .env

# Install dependencies
composer install

# Generate app key
php artisan key:generate

# Run migrations + seed demo data
php artisan migrate --seed

# Install Passport keys + personal access client
php artisan passport:keys
php artisan passport:client --personal --name="SaaS API"

# Start the server
php artisan serve --port=8000
```

### Frontend Setup

```bash
cd frontend
npm install
npm run dev
```

Open **http://localhost:3000** and log in with:
- **Email:** `admin@demo.com`
- **Password:** `password`

---

## 🐳 Docker Compose

```bash
# Core services (backend + frontend + MySQL + Redis)
docker compose up -d

# With RabbitMQ
docker compose --profile messaging up -d

# With Apache Kafka
docker compose --profile kafka up -d

# With phpMyAdmin
docker compose --profile dev up -d
```

| Service | URL |
|---|---|
| Backend API | http://localhost:8000/api |
| React Frontend | http://localhost:3000 |
| phpMyAdmin | http://localhost:8080 |
| RabbitMQ Management | http://localhost:15672 |

---

## 📡 API Reference

### Authentication
```
POST /api/auth/login              { email, password }
POST /api/auth/logout             Bearer token
GET  /api/auth/me                 Bearer token
POST /api/auth/refresh            Bearer token
POST /api/auth/sso/callback       { sso_token, email }
```

> All tenant-scoped endpoints require `X-Tenant-ID: {id}` header (or `?tenant_id=1`).

### Users
```
GET    /api/users              ?per_page=10&page=1&search=john&role=admin
POST   /api/users              { name, email, password, roles[], is_active, attributes }
GET    /api/users/{id}
PUT    /api/users/{id}
DELETE /api/users/{id}
```

### Products
```
GET    /api/products           ?per_page=10&page=1&search=widget&category=gadgets
POST   /api/products           { name, sku, price, category, description, attributes }
GET    /api/products/{id}
PUT    /api/products/{id}
DELETE /api/products/{id}
```

### Inventory (cross-service product-name filtering)
```
GET    /api/inventory          ?per_page=10&product_name=widget&low_stock=1
POST   /api/inventory          { product_id, warehouse_location, quantity, reorder_level }
GET    /api/inventory/{id}
PUT    /api/inventory/{id}
DELETE /api/inventory/{id}
POST   /api/inventory/{id}/adjust-stock   { delta, reason }
```

### Orders (Saga pattern)
```
GET    /api/orders             ?per_page=10&status=pending
POST   /api/orders             { items:[{product_id,quantity}], shipping_address, payment_method }
GET    /api/orders/{id}
PATCH  /api/orders/{id}/status { status }
POST   /api/orders/{id}/cancel { reason }
```

### Tenant Configuration
```
GET  /api/admin/tenants/{id}/config
POST /api/admin/tenants/{id}/config   { key, value, group, type }
```

---

## 🏗️ Project Structure

```
├── backend/
│   ├── app/
│   │   ├── Helpers/PaginationHelper.php      # Conditional pagination
│   │   ├── Infrastructure/MessageBroker/     # Null · RabbitMQ · Kafka
│   │   ├── Interfaces/                       # MessageBroker · Repository · Saga
│   │   ├── Middleware/                       # TenantMiddleware · AuthorizationMiddleware
│   │   ├── Models/                           # Tenant · TenantConfig · User · Product · Inventory · Order · OrderItem
│   │   ├── Modules/
│   │   │   ├── Auth/Controllers/AuthController.php
│   │   │   ├── User/{Controllers,Services,Repositories}/
│   │   │   ├── Product/{Controllers,Services,Repositories}/
│   │   │   ├── Inventory/{Controllers,Services,Repositories}/
│   │   │   ├── Order/{Controllers,Services,Repositories}/
│   │   │   └── Tenant/Controllers/TenantController.php
│   │   ├── Sagas/OrderSaga.php               # Saga + compensating transactions
│   │   └── Services/TenantConfigService.php
│   ├── database/
│   │   ├── migrations/                       # 7 migrations
│   │   └── seeders/DatabaseSeeder.php        # Demo tenant · roles · users · products
│   ├── routes/api.php
│   └── tests/
│       ├── Unit/PaginationHelperTest.php
│       └── Feature/AuthTest.php
│
├── frontend/
│   └── src/
│       ├── api/         # client · auth · users · products · inventory · orders · tenants
│       ├── components/  # Layout · ProtectedRoute · DataTable · Pagination
│       ├── contexts/    # AuthContext · TenantContext
│       ├── pages/       # Login · Dashboard · Users · Products · Inventory · Orders · TenantConfig
│       └── types/       # TypeScript interfaces
│
├── docker-compose.yml
└── README.md
```

---

## 🔐 Authentication & SSO

The system uses **Laravel Passport** for OAuth2 token-based authentication. Each user gets a personal access token with scoped permissions derived from their roles.

**SSO Flow:**
1. Identity provider authenticates the user
2. Provider calls `POST /api/auth/sso/callback` with `{ sso_token, email }`
3. System issues a Passport access token
4. React stores token in `localStorage` and attaches it to all API requests via `Authorization: Bearer ...`

---

## 🏢 Multi-Tenancy

Each request is scoped to a tenant via `TenantMiddleware`:

```
X-Tenant-ID: 1               (HTTP header — preferred)
?tenant_id=1                 (query parameter)
subdomain.yourdomain.com     (subdomain resolution)
```

**Tenant Runtime Configuration** — settings vary per tenant without affecting others:

```http
POST /api/admin/tenants/1/config
{ "key": "mail_from_address", "value": "hello@company.com", "group": "mail", "type": "string" }
```

Supported config groups: `mail` · `payment` · `notification` · `general`

---

## 🛡️ RBAC + ABAC

**Roles:** `super_admin` · `admin` · `manager` · `staff` · `viewer`

**ABAC example** — `AuthorizationMiddleware` evaluates user `attributes` JSON:
```json
{ "is_department_head": true, "extra_permissions": ["inventory.adjust"] }
```

---

## 🔄 Saga Pattern

`OrderSaga` orchestrates a distributed transaction:

```
Step 1: validate_order        validate items and quantities
Step 2: reserve_inventory     atomically reserve stock (DB transaction)
Step 3: create_order          persist order + items
Step 4: confirm_payment       call payment gateway (simulated)
Step 5: confirm_order         set status = confirmed

On failure → compensate in reverse:
  compensateConfirmOrder      set status = cancelled
  compensateConfirmPayment    refund payment
  compensateCreateOrder       delete order record
  compensateReserveInventory  release reserved stock
```

---

## 📨 Event-Driven Architecture

Services publish domain events via `MessageBrokerInterface`:

```php
$this->messageBroker->publish('order.confirmed', ['order_id' => 123, 'tenant_id' => 1]);
```

Switch broker via `.env`:
```
MESSAGE_BROKER_DRIVER=null       # default (log-only)
MESSAGE_BROKER_DRIVER=rabbitmq   # RabbitMQ
MESSAGE_BROKER_DRIVER=kafka      # Apache Kafka
```

---

## 📄 Conditional Pagination

```
GET /api/products                  → returns all records (Collection)
GET /api/products?per_page=10&page=2  → returns paginated LengthAwarePaginator
```

Works for Eloquent Builder, Collection, and plain arrays.

---

## 🧪 Tests

```bash
cd backend && php artisan test
```

---

## 📝 License

MIT
