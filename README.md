# AutoERP

A production-ready, enterprise-grade multi-tenant SaaS ERP/CRM platform built with **Laravel 11** (PHP 8.3) and **Vue 3**.

[![CI](https://github.com/kasunvimarshana/AutoERP/actions/workflows/ci.yml/badge.svg)](https://github.com/kasunvimarshana/AutoERP/actions/workflows/ci.yml)
[![Tests](https://github.com/kasunvimarshana/AutoERP/actions/workflows/tests.yml/badge.svg)](https://github.com/kasunvimarshana/AutoERP/actions/workflows/tests.yml)

---

## Architecture Overview

```
Presentation Layer  → API Controllers (v1), Form Requests, JSON Resources
Application Layer   → Services (PricingEngine, InventoryService, OrderService …)
Domain Layer        → Eloquent Models, Domain Events, Enums, Value Objects
Infrastructure      → Repository Pattern, Migrations, Queued Jobs, Webhooks
```

### Core Principles

| Principle | Implementation |
|-----------|---------------|
| **Multi-tenancy** | Row-level isolation via `tenant_id` on every entity |
| **Hierarchical Orgs** | Nested set model (`lft/rgt/depth`) for org trees |
| **JWT Auth** | Stateless, multi-guard — user × device × org claims |
| **RBAC / ABAC** | Spatie Permissions + Laravel Policies + Form Request `authorize()` |
| **Event-Driven** | Laravel events → queued jobs → Webhook delivery pipeline |
| **Precision Finance** | BCMath (8 decimal places) for all monetary calculations |
| **Audit Trail** | Immutable `audit_logs` on every state change |
| **Optimistic Locking** | `lock_version` on Product, Order, Invoice, StockItem |
| **Pessimistic Locking** | `lockForUpdate()` in InventoryService stock adjustments |
| **Repository Pattern** | `BaseRepository` + Product / Order repository implementations |
| **Service Contracts** | All major services bound to interfaces via `AppServiceProvider` |
| **FIFO / FEFO** | `stock_batches` cost layers; FEFO sorts by expiry, FIFO by receipt date |

---

## Implemented Modules

| Module | Status | Key Features |
|--------|--------|-------------|
| **Auth** | ✅ | JWT login/logout/refresh, brute-force throttle |
| **Tenant** | ✅ | Create, suspend, activate tenants |
| **Organization** | ✅ | Hierarchical org tree (nested set) |
| **User** | ✅ | CRUD, suspend/activate |
| **RBAC** | ✅ | Roles, permissions, seeder |
| **Product** | ✅ | Goods, Service, Digital, Bundle, Composite; variants; categories; units |
| **Pricing Engine** | ✅ | Flat, Percentage, Tiered, **Conditional**, Rule-Based pricing |
| **Inventory** | ✅ | Multi-warehouse, FIFO/FEFO valuation, batch/lot/serial/expiry tracking |
| **Stock Alerts** | ✅ | Low-stock alerts (reorder point), expiry alerts |
| **Order** | ✅ | Draft → Confirmed → Cancelled lifecycle |
| **Invoice** | ✅ | Draft → Sent → Void lifecycle |
| **Payment** | ✅ | Multi-method payments linked to invoices |
| **POS** | ✅ | Point-of-sale transactions, cash register management |
| **POS Returns** | ✅ | Full / partial return & refund workflow |
| **Purchase** | ✅ | Supplier purchase orders with line items |
| **Expense** | ✅ | Expense categories and records |
| **Stock Adjustment** | ✅ | Audited manual stock adjustments with reasons |
| **CRM** | ✅ | Contacts, leads, follow-ups, pipeline stages |
| **HR** | ✅ | Employees, leave requests, payroll |
| **Accounting** | ✅ | Chart of accounts, journal entries, periods |
| **Reporting** | ✅ | Sales, inventory, receivables, POS, purchase, expense summaries |
| **Notification** | ✅ | In-app notifications; multi-channel support |
| **File Manager** | ✅ | File uploads, folder management |
| **Audit Log** | ✅ | Immutable audit trail for all domain events |
| **Webhook** | ✅ | Configurable webhooks with retry delivery queue |
| **Tax Rate** | ✅ | Flexible tax rate groups |
| **Brand** | ✅ | Product brands linked to product catalog |
| **Customer Group** | ✅ | Tiered customer segmentation |
| **Business Location** | ✅ | Multi-location support with payment accounts |
| **Variation Templates** | ✅ | Reusable attribute templates for product variants |
| **Currency** | ✅ | Multi-currency support |
| **Selling Price Groups** | ✅ | Configurable price overrides per customer group |
| **Vue 3 SPA Frontend** | ✅ | Login, Dashboard, Products, Orders, Invoices, Users, Inventory pages |
| **HTTPS Enforcement** | ✅ | `ForceHttps` middleware + HSTS (set `FORCE_HTTPS=true` in production) |

---

## API Reference (v1)

All endpoints are prefixed with `/api/v1` and require `Authorization: Bearer <JWT>` unless marked **Public**.

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | **Public** — obtain JWT |
| POST | `/auth/logout` | Invalidate token |
| POST | `/auth/refresh` | Rotate token |
| GET  | `/auth/me` | Current user profile |

### Inventory & Stock
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/inventory/stock` | Paginated stock levels |
| GET | `/inventory/alerts/low-stock` | Items at or below reorder point |
| GET | `/inventory/alerts/expiring` | Batches expiring within N days (default 30) |
| GET | `/inventory/fifo-cost` | FIFO weighted-average cost for a SKU |
| GET/POST/PUT/DELETE | `/warehouses` | Warehouse CRUD |

### Pricing
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST/PUT/DELETE | `/price-lists` | Price list CRUD |
| GET | `/price-lists/{id}/rules` | List pricing rules |
| POST | `/price-lists/{id}/rules` | Add pricing rule (flat/percentage/tiered/conditional/rule_based) |

> See `routes/api.php` for the full endpoint list.

---

## Quick Start

```bash
# 1. Clone and install
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
# Edit .env — set DB_* and JWT_SECRET

# 3. Migrate + seed
php artisan migrate
php artisan db:seed

# 4. Run
composer run dev        # starts server + queue + logs + Vite in one command
```

### Testing

```bash
php artisan test                    # full suite (SQLite in-memory)
php artisan test tests/Feature/...  # specific test file
./vendor/bin/pint --test            # code style check
```

---

## Project Tracking

See [`MODULE_STATUS.md`](MODULE_STATUS.md) for detailed implementation status per module.

---

## License

MIT © kasunvimarshana
