# ERP/CRM Module Implementation Status

## Architecture Overview

```
Presentation Layer  → Controllers, Requests, Resources (API responses)
Application Layer   → Services, Commands, Queries, DTOs
Domain Layer        → Models, Events, Contracts, Value Objects
Infrastructure Layer → Repositories, Migrations, External Services
```

### Core Principles
- **Multi-tenancy**: Row-level isolation via `tenant_id` on all entities
- **Hierarchical Orgs**: Nested set model (lft/rgt/depth) for organization trees
- **JWT Auth**: Stateless, multi-guard (user × device × org claims in token)
- **RBAC/ABAC**: Spatie permissions + Laravel Policies (Product, Order, Invoice) + Form Request authorize()
- **Event-Driven**: Laravel events + queued jobs (DeliverWebhookJob) + WebhookEventSubscriber pipeline
- **Precision Finance**: BCMath (8 decimal places) for ALL monetary calculations
- **Audit Trail**: Immutable audit_logs on all state changes
- **Optimistic Locking**: `lock_version` on Product, Order, Invoice, StockItem
- **Pessimistic Locking**: `lockForUpdate()` in InventoryService for stock adjustments
- **Repository Pattern**: BaseRepository + ProductRepository + OrderRepository implementations
- **Service Contracts**: All major services bound to interfaces via AppServiceProvider
- **Cache Layer**: Laravel cache (Redis / DB) for business settings + in-memory per-request fallback
- **HTTPS Enforcement**: ForceHttps middleware with HSTS header (configurable via FORCE_HTTPS env var)
- **Vue 3 SPA**: Composition API + Pinia + Vue Router 4, served via `app.blade.php` catch-all

---

## Module Status

| Module | Domain Models | Migrations | Services | Controllers | Routes | Tests | Status |
|--------|--------------|------------|----------|-------------|--------|-------|--------|
| **Platform** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Auth** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Tenant** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Organization** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **User** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **RBAC** | ✅ | ✅ | ✅ (Seeder) | ✅ | ✅ | ✅ | ✅ Done |
| **Product** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Pricing Engine** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Inventory** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Stock Alerts (low-stock/expiry)** | ✅ | ✅ (stock_batches) | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **FIFO / FEFO Valuation** | ✅ (StockBatch) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Order** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Invoice** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Payment** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **CRM** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **HR** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Accounting** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Reporting** | N/A | N/A | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Notification** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **File Manager** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Audit** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Webhook** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Tax Rate** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Brand** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Customer Group** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Business Location** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Payment Account** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **POS (Point of Sale)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Purchase/Procurement** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Expense** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Stock Adjustment** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Brand→Product link** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Variation Templates** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Currency** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Selling Price Groups** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **POS Return/Refund** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Reference Numbers** | ✅ | ✅ | ✅ | N/A | ✅ | N/A | ✅ Done |
| **Idempotency Keys** | ✅ | ✅ | ✅ (Middleware) | N/A | ✅ (global api group) | ✅ | ✅ Done |
| **Business Settings** | ✅ | ✅ | ✅ + Cache | ✅ | ✅ | ✅ | ✅ Done |
| **Invoice Schemes** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Stock Transfers** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Barcode** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Invoice Layouts** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Printer** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Product Rack** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Restaurant Module** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Purchase Return** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Opening Stock** | N/A | N/A | N/A | ✅ | ✅ | ✅ | ✅ Done |
| **Sales Commission Agent** | ✅ (User) | ✅ | N/A | ✅ | ✅ | ✅ | ✅ Done |
| **Extended Reports (parity)** | N/A | N/A | ✅ | ✅ | ✅ | N/A | ✅ Done |
| **Product Category API** | ✅ (existed) | ✅ (existed) | ✅ | ✅ | ✅ | ✅ | ✅ Done |
| **Restaurant Kitchen Display** | ✅ (PosTransaction) | ✅ | N/A | ✅ | ✅ | N/A | ✅ Done |
| **User Contact Access** | ✅ | ✅ | N/A | ✅ | ✅ | ✅ | ✅ Done |
| **Workflow Engine** | ✅ (WorkflowDefinition, WorkflowState, WorkflowTransition, WorkflowInstance, WorkflowHistory) | ✅ | ✅ (WorkflowEngineService + Interface) | ✅ (WorkflowController) | ✅ (7 endpoints) | ✅ (15 tests) | ✅ Done |
| **Repository Layer** | ✅ | N/A | N/A | N/A | N/A | ✅ | ✅ Done |
| **Service Contracts** | ✅ | N/A | N/A | N/A | N/A | ✅ | ✅ Done |
| **Domain Events** | ✅ | N/A | N/A | N/A | N/A | ✅ | ✅ Done |
| **Event Listeners** | ✅ | N/A | N/A | N/A | N/A | N/A | ✅ Done |
| **Webhook Queue Job** | ✅ | N/A | N/A | N/A | N/A | N/A | ✅ Done |
| **Laravel Policies** | ✅ | N/A | N/A | N/A | N/A | ✅ | ✅ Done |
| **ForceHttps Middleware** | N/A | N/A | N/A | N/A | ✅ (global) | ✅ | ✅ Done |
| **Vue 3 SPA Frontend** | N/A | N/A | N/A | N/A | ✅ (catch-all) | N/A | ✅ Done |

### Status Legend
- ✅ Implemented
- 🟡 In Progress
- 🔴 Planned
- ⬜ Not Started

---

## API Endpoints (v1)

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | JWT login |
| POST | `/api/v1/auth/logout` | JWT logout |
| POST | `/api/v1/auth/refresh` | Refresh JWT token |
| GET | `/api/v1/auth/me` | Get current user profile |

### Platform (Tenant Management)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/platform/tenants` | List tenants |
| POST | `/api/v1/platform/tenants` | Create tenant |
| PUT | `/api/v1/platform/tenants/{id}` | Update tenant |
| PATCH | `/api/v1/platform/tenants/{id}/suspend` | Suspend tenant |
| PATCH | `/api/v1/platform/tenants/{id}/activate` | Activate tenant |

### Organizations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/organizations` | List organizations (paginated) |
| GET | `/api/v1/organizations/tree` | Organization hierarchy tree |
| POST | `/api/v1/organizations` | Create organization |
| PUT | `/api/v1/organizations/{id}` | Update organization |
| DELETE | `/api/v1/organizations/{id}` | Delete organization |

### Users
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/users` | List users (tenant-scoped) |
| POST | `/api/v1/users` | Create user with roles |
| PUT | `/api/v1/users/{id}` | Update user |
| PATCH | `/api/v1/users/{id}/suspend` | Suspend user |
| PATCH | `/api/v1/users/{id}/activate` | Activate user |

### Products
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products` | List products (type/active/search filters) |
| POST | `/api/v1/products` | Create product (goods/service/digital/bundle/composite) |
| PUT | `/api/v1/products/{id}` | Update product (optimistic lock) |
| DELETE | `/api/v1/products/{id}` | Soft-delete product |

### Inventory
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/warehouses` | List warehouses |
| POST | `/api/v1/warehouses` | Create warehouse |
| PUT | `/api/v1/warehouses/{id}` | Update warehouse |
| DELETE | `/api/v1/warehouses/{id}` | Delete warehouse |

### Orders
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/orders` | List orders |
| POST | `/api/v1/orders` | Create order with lines (BCMath totals) |
| PATCH | `/api/v1/orders/{id}/confirm` | Confirm order |
| PATCH | `/api/v1/orders/{id}/cancel` | Cancel order |

### Invoices
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/invoices` | List invoices |
| POST | `/api/v1/invoices` | Create invoice with items |
| PATCH | `/api/v1/invoices/{id}/send` | Send invoice |
| PATCH | `/api/v1/invoices/{id}/void` | Void invoice |

### Payments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/payments` | List payments |
| POST | `/api/v1/payments` | Record payment (auto-updates invoice status) |

### CRM
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/crm/contacts` | List contacts |
| POST | `/api/v1/crm/contacts` | Create contact |
| PUT | `/api/v1/crm/contacts/{id}` | Update contact |
| DELETE | `/api/v1/crm/contacts/{id}` | Delete contact |
| GET | `/api/v1/crm/leads` | List leads |
| POST | `/api/v1/crm/leads` | Create lead |
| PATCH | `/api/v1/crm/leads/{id}/convert` | Convert lead |
| GET | `/api/v1/crm/opportunities` | List opportunities |
| POST | `/api/v1/crm/opportunities` | Create opportunity |

### RBAC
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/roles` | List roles with permissions |
| POST | `/api/v1/roles` | Create role |
| PUT | `/api/v1/roles/{id}` | Update role |
| DELETE | `/api/v1/roles/{id}` | Delete role |
| PATCH | `/api/v1/roles/{id}/sync-permissions` | Sync role permissions |
| GET | `/api/v1/roles/permissions` | List all permissions |

### Pricing
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/price-lists` | List price lists |
| POST | `/api/v1/price-lists` | Create price list |
| PUT | `/api/v1/price-lists/{id}` | Update price list |
| DELETE | `/api/v1/price-lists/{id}` | Delete price list |
| GET | `/api/v1/price-lists/{id}/rules` | List rules for a price list |
| POST | `/api/v1/price-lists/{id}/rules` | Add rule to price list |

### Audit
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/audit-logs` | List audit logs (filterable) |

### HR
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/hr/employees` | List employees |
| POST | `/api/v1/hr/employees` | Create employee |
| PUT | `/api/v1/hr/employees/{id}` | Update employee |
| PATCH | `/api/v1/hr/employees/{id}/terminate` | Terminate employee |
| GET | `/api/v1/hr/departments` | List departments |
| POST | `/api/v1/hr/departments` | Create department |
| PUT | `/api/v1/hr/departments/{id}` | Update department |
| GET | `/api/v1/hr/leave-requests` | List leave requests |
| POST | `/api/v1/hr/leave-requests` | Create leave request |
| PATCH | `/api/v1/hr/leave-requests/{id}/approve` | Approve leave request |
| PATCH | `/api/v1/hr/leave-requests/{id}/reject` | Reject leave request |

### Accounting
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/accounting/accounts` | List chart of accounts |
| POST | `/api/v1/accounting/accounts` | Create account |
| PUT | `/api/v1/accounting/accounts/{id}` | Update account |
| GET | `/api/v1/accounting/periods` | List accounting periods |
| POST | `/api/v1/accounting/periods` | Create period |
| PATCH | `/api/v1/accounting/periods/{id}/close` | Close period |
| GET | `/api/v1/accounting/journal-entries` | List journal entries |
| POST | `/api/v1/accounting/journal-entries` | Create journal entry (balanced) |
| PATCH | `/api/v1/accounting/journal-entries/{id}/post` | Post journal entry |

### Notifications
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/notifications/templates` | List notification templates |
| POST | `/api/v1/notifications/templates` | Create template |
| PUT | `/api/v1/notifications/templates/{id}` | Update template |
| POST | `/api/v1/notifications/send` | Send notification |
| GET | `/api/v1/notifications/logs` | List notification logs |

### File Manager
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/files` | List uploaded files |
| POST | `/api/v1/files` | Upload file |
| DELETE | `/api/v1/files/{id}` | Delete file |

### Reports
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/reports/sales-summary` | Sales summary by status/period |
| GET | `/api/v1/reports/inventory-summary` | Inventory summary |
| GET | `/api/v1/reports/receivables-summary` | Receivables by status |
| GET | `/api/v1/reports/top-products` | Top products by revenue |
| GET | `/api/v1/reports/pos-sales-summary` | POS sales summary by location |
| GET | `/api/v1/reports/purchase-summary` | Purchase summary by status |
| GET | `/api/v1/reports/expense-summary` | Expense summary by category |
| GET | `/api/v1/reports/profit-loss` | Profit & Loss: gross sales − COGS − expenses |
| GET | `/api/v1/reports/tax-report` | Tax collected on sales |
| GET | `/api/v1/reports/stock-expiry` | Items expiring within N days |
| GET | `/api/v1/reports/register-report` | Cash register pay-in / pay-out summary |
| GET | `/api/v1/reports/customer-group` | Sales breakdown by customer group |
| GET | `/api/v1/reports/product-sell` | Product-level sell quantities & revenue |
| GET | `/api/v1/reports/product-purchase` | Product-level purchase quantities & cost |
| GET | `/api/v1/reports/sales-representative` | Sales by commission agent |
| GET | `/api/v1/reports/trending-products` | Top products by units sold |
| GET | `/api/v1/reports/lot-report` | Stock on hand grouped by lot number |

### Barcodes
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/barcodes` | List barcode label templates |
| POST | `/api/v1/barcodes` | Create barcode template |
| PUT | `/api/v1/barcodes/{id}` | Update barcode template |
| DELETE | `/api/v1/barcodes/{id}` | Delete barcode template |
| PATCH | `/api/v1/barcodes/{id}/set-default` | Set default barcode template |

### Invoice Layouts
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/invoice-layouts` | List invoice layout templates |
| POST | `/api/v1/invoice-layouts` | Create invoice layout |
| PUT | `/api/v1/invoice-layouts/{id}` | Update invoice layout |
| DELETE | `/api/v1/invoice-layouts/{id}` | Delete invoice layout |
| PATCH | `/api/v1/invoice-layouts/{id}/set-default` | Set default invoice layout |

### Printers
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/printers` | List configured printers |
| POST | `/api/v1/printers` | Create printer |
| PUT | `/api/v1/printers/{id}` | Update printer |
| DELETE | `/api/v1/printers/{id}` | Delete printer |
| GET | `/api/v1/printers/capability-profiles` | Available capability profiles |
| GET | `/api/v1/printers/connection-types` | Available connection types |

### Product Racks
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/product-racks` | List product rack/shelf locations |
| POST | `/api/v1/product-racks` | Create or update rack location (upsert) |
| DELETE | `/api/v1/product-racks/{id}` | Delete rack entry |

### Restaurant Module
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/restaurant/tables` | List restaurant tables |
| POST | `/api/v1/restaurant/tables` | Create table |
| PUT | `/api/v1/restaurant/tables/{id}` | Update table |
| DELETE | `/api/v1/restaurant/tables/{id}` | Delete table |
| GET | `/api/v1/restaurant/modifier-sets` | List modifier sets (add-ons) |
| POST | `/api/v1/restaurant/modifier-sets` | Create modifier set with options |
| PUT | `/api/v1/restaurant/modifier-sets/{id}` | Update modifier set + options |
| DELETE | `/api/v1/restaurant/modifier-sets/{id}` | Delete modifier set |
| GET | `/api/v1/restaurant/bookings` | List table bookings |
| POST | `/api/v1/restaurant/bookings` | Create booking |
| PUT | `/api/v1/restaurant/bookings/{id}` | Update booking |
| DELETE | `/api/v1/restaurant/bookings/{id}` | Delete booking |

### Purchase Returns
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/purchase-returns` | List purchase returns |
| POST | `/api/v1/purchase-returns` | Create purchase return (with lines) |
| PATCH | `/api/v1/purchase-returns/{id}/cancel` | Cancel purchase return |

### Opening Stock
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/opening-stock` | List opening stock entries |
| POST | `/api/v1/opening-stock` | Record initial stock quantities |

### Sales Commission Agents
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sales-commission-agents` | List sales commission agents |
| GET | `/api/v1/sales-commission-agents/{id}/total-sell` | Agent's total sales in period |
| GET | `/api/v1/sales-commission-agents/{id}/total-commission` | Agent's commission amount in period |

### Webhooks
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/webhooks` | List webhooks |
| POST | `/api/v1/webhooks` | Create webhook |
| PUT | `/api/v1/webhooks/{id}` | Update webhook |
| DELETE | `/api/v1/webhooks/{id}` | Delete webhook |
| GET | `/api/v1/webhooks/{id}/deliveries` | List webhook deliveries |
| POST | `/api/v1/webhooks/{id}/test` | Test webhook delivery |

---

## Domain Entity Map

### Core (Platform)
- **Tenant** → top-level isolation boundary
- **Organization** → hierarchical (nested set), belongs to Tenant
- **User** → JWT subject + RBAC roles, belongs to Tenant + Organizations
- **AuditLog** → immutable polymorphic event record

### Product Domain
- **Unit** → configurable buy/sell units (quantity/weight/volume)
- **ProductCategory** → hierarchical product taxonomy
- **Product** → polymorphic type (goods/service/digital/bundle/composite), optimistic lock
- **ProductVariant** → SKU-level attributes
- **PriceList** → org/location/date-scoped price lists
- **PriceRule** → flat/percentage/tiered/rule_based pricing with BCMath

### Inventory Domain
- **Warehouse** → physical/virtual storage locations
- **StockItem** → current quantity with pessimistic locking
- **StockMovement** → immutable movement history

### Sales Domain
- **Order** → sale/purchase/return with BCMath totals, optimistic lock
- **OrderLine** → line items with precision quantities and prices
- **Invoice** → billing document with payment tracking
- **InvoiceItem** → invoice line items
- **Payment** → payment records, auto-reconciles invoice status

### CRM Domain
- **Contact** → person or company
- **Lead** → sales opportunity in early stage
- **Opportunity** → qualified sales pipeline entry

### HR Domain
- **Department** → hierarchical org units with manager
- **Employee** → staff members with employment details and leave tracking
- **LeaveType** → configurable leave categories (annual, sick, etc.)
- **LeaveRequest** → approval workflow for employee leave

### Accounting Domain
- **ChartOfAccount** → hierarchical account tree (asset/liability/equity/revenue/expense)
- **AccountingPeriod** → fiscal periods with open/closed/locked lifecycle
- **JournalEntry** → double-entry bookkeeping with balanced debit/credit lines
- **JournalEntryLine** → individual debit/credit entries

### Notification Domain
- **NotificationTemplate** → reusable channel-aware templates with `{{variable}}` interpolation
- **NotificationLog** → delivery records for all sent notifications

### File Manager Domain
- **FileCategory** → organizational taxonomy for files
- **MediaFile** → polymorphic attachments with storage abstraction and checksum

### Webhook Domain
- **Webhook** → subscriber endpoints with event filters and retry config
- **WebhookDelivery** → per-delivery attempt records with HTTP response tracking

---

## RBAC Roles (67 permissions across 5 roles)

| Role | Description |
|------|-------------|
| `super-admin` | All permissions (*) |
| `tenant-admin` | Full tenant management |
| `manager` | Operational management |
| `staff` | Day-to-day operations |
| `viewer` | Read-only access |

---

## Infrastructure

| Concern | Technology | Status |
|---------|-----------|--------|
| Framework | Laravel 11 (PHP 8.3) | ✅ |
| Auth | tymon/jwt-auth 2.x | ✅ |
| Permissions | spatie/laravel-permission 6.x | ✅ |
| Database | SQLite (dev) / MySQL / PostgreSQL | ✅ |
| Monetary Math | BCMath (8 decimal places) | ✅ |
| Optimistic Lock | lock_version on critical models | ✅ |
| Pessimistic Lock | lockForUpdate() in InventoryService | ✅ |
| Soft Deletes | All domain models | ✅ |
| Audit Trail | AuditService + AuditLog model | ✅ |
| Domain Events | OrderCreated, InvoiceCreated, PaymentRecorded, ProductCreated, StockAdjusted, WorkflowTransitioned | ✅ |
| Cache | Redis (configurable via .env) | ⬜ |
| Queue | Redis / Database (configurable) | ⬜ |
| Storage | Local / S3 (configurable) | ⬜ |
| Frontend | Vue 3 + Vite + Pinia + Vue Router 4 | ✅ |
| API Docs | OpenAPI 3.1 (docs/openapi.yaml) | ✅ |
| Testing | PHPUnit 11 (226 tests passing, 351 assertions) | ✅ |
| CI/CD | GitHub Actions (ci.yml + tests.yml) | ✅ |

---

## Security Checklist

- [x] JWT stateless auth (no sessions) — per user×device×org
- [x] Tenant isolation via `tenant_id` on all entities
- [x] RBAC via Spatie permissions (48 permissions, 5 roles)
- [x] Middleware: TenantMiddleware, SetLocale, EnsureOrganizationAccess, IdempotencyMiddleware
- [x] Idempotency Keys for all POST/PUT/PATCH (prevent duplicate mutations on retry)
- [x] Soft deletes on all domain entities
- [x] Immutable AuditLog for all state changes
- [x] BCMath precision for all financial calculations
- [x] Optimistic locking (lock_version) for concurrent updates
- [x] Pessimistic locking (lockForUpdate) for inventory
- [x] Input validation via Form Requests
- [x] Permission checks in controllers (can() + abort_unless)
- [x] SQL injection prevention (Eloquent parameterized queries)
- [x] PHP 8.3 minimum (no insecure PHP 8.2)
- [x] Rate limiting (120 req/min auth users; 30 req/min guests; 10 req/min on login)
- [x] CORS configuration (env-configurable via CORS_ALLOWED_ORIGINS)
- [x] HTTPS enforcement via `ForceHttps` middleware (set `FORCE_HTTPS=true` in production) + HSTS

