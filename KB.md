Below is the **Enterprise SaaS ERP Knowledge Base (Authoritative Reference Edition)**.

This knowledge base consolidates:

* Industry ERP standards
* Modular SaaS architecture patterns
* Multi-tenant best practices
* Enterprise accounting doctrine
* Inventory & costing methodologies
* Customization & metadata engines
* Concurrency & financial precision rules
* Plugin marketplace design principles
* Localization & internationalization patterns
* E-commerce & POS integration knowledge
* Frontend architecture guidance
* Business process modeling & workflow patterns
* Barcode / QR / GS1 standards
* OpenAPI documentation standards
* Laravel-specific implementation patterns
* **Controller-Service-Repository architecture pattern** (added v3.0)
* **Native Laravel solution-first strategy** (added v3.0)

---

# ENTERPRISE SaaS ERP KNOWLEDGE BASE

Version: 3.0
Purpose: Foundational reference for building a fully customizable, reusable SaaS ERP platform

---

# CONTROLLER – SERVICE – REPOSITORY PATTERN

## Overview

Every request flow in this platform MUST pass through exactly three layers:

```
HTTP Request
    ↓
Controller  (Interfaces/Http/Controllers)
    ↓
Service / Handler  (Application/Handlers or Application/Services)
    ↓
Repository  (Infrastructure/Repositories, bound via Domain/Contracts)
    ↓
Database / External System
```

Reference: https://oneuptime.com/blog/post/2026-02-03-laravel-repository-pattern/view

## Layer Responsibilities

### Controller Layer
- Receives HTTP request and validates input (or delegates to FormRequest)
- Constructs Command/DTO and passes to Service/Handler
- Returns formatted JSON response using standard envelope
- **MUST NOT** contain business logic
- **MUST NOT** reference Eloquent Models or query builder directly
- **MUST NOT** call Repositories directly (except for read-only lookups where no handler exists yet)

### Service / Handler Layer (Application)
- Implements one use-case per class
- Orchestrates domain entities and repository calls
- Enforces domain rules (validation, state transitions)
- Wraps write operations in `DB::transaction()`
- **MUST NOT** reference HTTP layer (Request, Response)
- **MUST** use Repository interfaces (never Eloquent Models directly)

### Repository Layer (Domain Contract + Infrastructure Implementation)
- `Domain/Contracts/XxxRepositoryInterface.php` — pure PHP interface
- `Infrastructure/Repositories/XxxRepository.php` — Eloquent implementation
- Maps between Eloquent Models and Domain Entities (`toDomain()` method)
- All queries are encapsulated here
- Bound via ServiceProvider: `$this->app->bind(Interface::class, Implementation::class)`

## Module Directory Structure (Canonical)

```
Modules/{ModuleName}/
├── Application/
│   ├── Commands/          CreateXxxCommand.php, UpdateXxxCommand.php
│   ├── Handlers/          CreateXxxHandler.php, UpdateXxxHandler.php
│   ├── DTOs/              (optional data transfer objects)
│   ├── Queries/           (optional CQRS read models)
│   └── Services/          (optional domain services, e.g. BarcodeService)
├── Domain/
│   ├── Contracts/         XxxRepositoryInterface.php
│   ├── Entities/          Xxx.php (pure PHP, no Eloquent)
│   ├── Enums/             XxxStatus.php
│   └── ValueObjects/      Money.php, Email.php, SKU.php
├── Infrastructure/
│   ├── Database/
│   │   └── Migrations/
│   ├── Models/            Xxx.php (Eloquent, with global scopes)
│   └── Repositories/      XxxRepository.php (implements interface)
├── Interfaces/
│   └── Http/
│       ├── Controllers/   XxxController.php
│       ├── Requests/      CreateXxxRequest.php
│       └── Resources/     XxxResource.php
└── Providers/
    └── XxxServiceProvider.php
```

## Standard Command + Handler Pattern

```php
// Command (immutable DTO)
final readonly class CreateXxxCommand {
    public function __construct(
        public int    $tenantId,
        public string $name,
        // ...
    ) {}
}

// Handler (service layer)
class CreateXxxHandler {
    public function __construct(
        private readonly XxxRepositoryInterface $repo,
    ) {}

    public function handle(CreateXxxCommand $cmd): XxxEntity {
        // 1. Domain validation
        // 2. Build domain entity
        // 3. Persist via repository
        return DB::transaction(fn() => $this->repo->save($entity));
    }
}

// Controller
class XxxController extends Controller {
    public function __construct(
        private readonly CreateXxxHandler       $createHandler,
        private readonly XxxRepositoryInterface $repo,
    ) {}

    public function store(Request $request): JsonResponse {
        $validated = $request->validate([...]);
        $entity = $this->createHandler->handle(new CreateXxxCommand(...));
        return response()->json(['success' => true, 'data' => [...], 'errors' => null], 201);
    }
}
```

## Repository Interface Pattern

```php
// Domain/Contracts/XxxRepositoryInterface.php
interface XxxRepositoryInterface {
    public function findById(int $id, int $tenantId): ?Xxx;
    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;
    public function save(Xxx $entity): Xxx;
    public function delete(int $id, int $tenantId): void;
}

// Infrastructure/Repositories/XxxRepository.php
class XxxRepository implements XxxRepositoryInterface {
    public function findById(int $id, int $tenantId): ?XxxEntity {
        $m = XxxModel::withoutGlobalScope('tenant')
            ->where('id', $id)->where('tenant_id', $tenantId)->first();
        return $m ? $this->toDomain($m) : null;
    }
    // ... other methods ...
    private function toDomain(XxxModel $m): XxxEntity {
        return new XxxEntity(id: $m->id, ...);
    }
}

// Providers/XxxServiceProvider.php
$this->app->bind(XxxRepositoryInterface::class, XxxRepository::class);
```

## Prohibited Patterns

| Violation | Correct Alternative |
|---|---|
| `$model = SomeModel::create([...])` in Controller | Use Handler → Repository |
| `SomeModel::where('tenant_id', $id)->get()` in Controller | Use Repository `findAll()` |
| `SomeModel::find($id)` in Handler | Use Repository `findById()` |
| Handler without constructor-injected Repository | Always inject via interface |
| Repository returning Eloquent Model | Repository returns Domain Entity |
| Cross-module Model import | Use Cross-module Contract/Event |

## Native Laravel Solutions (Preferred over Third-Party)

| Need | Native Laravel Solution | Avoid |
|---|---|---|
| Authentication | `Illuminate\Auth` + JWT or Sanctum | Custom auth from scratch |
| Validation | `$request->validate()` / FormRequest | Manual `isset()` chains |
| DB Transactions | `DB::transaction()` | Manual BEGIN/COMMIT |
| Queues | `Illuminate\Queue` | Custom queue tables |
| Events | `Illuminate\Events` | Direct coupling |
| Caching | `Illuminate\Cache` | Direct Redis calls |
| Localization | `Lang::get()` / `__()` | Hardcoded strings |
| File Storage | `Storage::disk()` | Direct file_put_contents |
| Password Hashing | `password_hash()` with BCRYPT | md5/sha1 |
| Rate Limiting | `RateLimiter` middleware | Custom throttle tables |
| Scheduled Jobs | `Illuminate\Console\Scheduling` | Cron scripts |
| Testing | PHPUnit + `RefreshDatabase` | External test runners |

---

# ERP FOUNDATIONAL PRINCIPLES

## ERP Definition

An ERP (Enterprise Resource Planning) system integrates:

* Finance
* Inventory
* Sales
* Procurement
* CRM
* Operations
* Human resources (optional extension)

Core ERP characteristics:

* Single source of truth
* Transaction-driven
* Financially reconcilable
* Cross-module traceable
* Audit-safe

---

# SaaS ERP ARCHITECTURAL KNOWLEDGE

## Multi-Tenant Models

Three models:

1. Shared DB, Shared Schema (row-level isolation)
2. Shared DB, Separate Schema
3. Separate DB per Tenant

Recommended default for scalable SaaS ERP:
Shared DB + Strict Row-Level Isolation + Optional DB-per-tenant upgrade path.

Mandatory:

* tenant_id on all business tables
* Tenant-scoped cache
* Tenant-scoped queues
* Tenant-scoped storage
* Tenant-scoped configs

---

# DOMAIN KNOWLEDGE – CORE ERP

---

# PRODUCT DOMAIN

## Product Types

* Stockable (Physical)
* Consumable
* Service
* Digital
* Bundle (Kit)
* Composite (Manufactured)
* Variant-based

## Key Concepts

* SKU
* UOM (Unit of Measure)
* Conversion Matrix
* Costing Method
* Valuation Method
* Traceability (Serial / Batch / Lot)
* GS1 compatibility (optional enterprise feature)
* Multi-image management
* Multi-location pricing
* Multi-currency pricing

## Costing Methods

* FIFO (First In First Out)
* LIFO (Last In First Out)
* Weighted Average

Inventory valuation impacts accounting directly.

---

# INVENTORY KNOWLEDGE

Inventory is:

* Ledger-driven
* Transactional
* Immutable (historical entries)

Key rules:

* Stock is never edited directly
* All changes via transactions
* Reservation precedes deduction
* Deduction must be atomic

Critical flows:

* Purchase Receipt
* Sales Shipment
* Internal Transfer
* Adjustment
* Return

Concurrency control is mandatory.

---

# ACCOUNTING KNOWLEDGE

ERP-grade accounting requires:

## Double-Entry Bookkeeping

Every transaction must:

Debit one account
Credit another account

Total Debits = Total Credits

## Core Accounting Structures

* Chart of Accounts
* Journal Entries
* Fiscal Periods
* Tax Rules
* Trial Balance
* Profit & Loss
* Balance Sheet

## Financial Integrity Rules

* No floating-point arithmetic
* Arbitrary precision decimals only
* Deterministic rounding
* Immutable journal entries

Accounting must reconcile with inventory valuation.

---

# SALES DOMAIN

Sales Flow:

Quotation → Sales Order → Delivery → Invoice → Payment

Important Concepts:

* Discount engine
* Tax calculation
* Commission engine
* Split payments
* Refund workflows
* Backorders

POS requires:

* Offline-first design
* Local transaction queue
* Sync reconciliation engine

---

# PROCUREMENT DOMAIN

Procurement Flow:

Purchase Request → RFQ → Vendor Selection → Purchase Order → Goods Receipt → Vendor Bill → Payment

Important:

* Three-way matching (PO, Receipt, Invoice)
* Vendor scoring
* Price comparison logic

---

# CRM DOMAIN

CRM is pipeline-driven:

Lead → Opportunity → Proposal → Closed Won/Lost

Must support:

* Activity tracking
* SLA timers
* Campaign attribution
* Customer segmentation
* Notes & attachments

---

# CUSTOMIZATION KNOWLEDGE

Enterprise SaaS ERP must be customizable without redeployment.

## Metadata-Driven Schema

* Custom fields
* Validation rules
* Dynamic forms
* Conditional visibility
* Computed fields

## Workflow Engine

State machine model:

State → Event → Transition → Guard → Action

Supports:

* Approval chains
* Escalation
* SLA enforcement
* Background tasks

## Rule Engine

Declarative rule patterns:

IF condition
THEN action

Used for:

* Pricing
* Discounts
* Taxes
* Commissions
* Inventory reservation logic

---

# CONCURRENCY & DATA SAFETY KNOWLEDGE

ERP systems are highly concurrent.

Mandatory controls:

* Pessimistic locking (stock deduction)
* Optimistic locking (updates)
* DB transactions
* Idempotency keys
* Version tracking

Stock and accounting must never be inconsistent.

---

# SECURITY KNOWLEDGE

ERP systems manage sensitive financial data.

Required:

* Role-based access control
* Attribute-based policies
* Tenant isolation enforcement
* Audit trails
* Suspicious activity detection
* Token rotation
* Secure hashing
* File validation

---

# API & INTEGRATION KNOWLEDGE

Modern ERP must be:

* API-first
* Versioned
* Idempotent
* Documented (OpenAPI)

Integration capabilities:

* Webhooks
* Event publishing
* Third-party connectors
* E-commerce sync
* Payment gateway support

---

# PERFORMANCE & SCALABILITY KNOWLEDGE

ERP bottlenecks typically occur in:

* Inventory deduction
* Accounting posting
* Reporting aggregation
* Workflow engines

Mitigation strategies:

* Event-driven design
* Queue processing
* Read replicas
* Caching abstraction
* Partitioned reporting tables
* Background reconciliation jobs

---

# AUDIT & COMPLIANCE KNOWLEDGE

ERP must support:

* Immutable logs
* Versioned records
* Traceable financial flows
* Historical state reconstruction
* Regulatory export capability

Audit trail is non-optional.

---

# REUSABILITY PRINCIPLES

Reusable ERP modules must:

* Avoid business-specific assumptions
* Be configuration-driven
* Avoid hardcoded logic
* Expose contracts, not implementations
* Remain independently replaceable

Design for:

* Multi-industry support
* Multi-country tax extension
* Marketplace plugin ecosystem

---

# PLUGIN MARKETPLACE KNOWLEDGE

A marketplace-ready ERP requires:

* Module manifest definition
* Dependency graph validation
* Version compatibility rules
* Sandboxed execution
* Tenant-scoped enablement
* Upgrade migration paths

---

# ENTERPRISE REPORTING KNOWLEDGE

Reports must support:

* Aggregated financial statements
* Inventory valuation reports
* Aging reports
* Tax summaries
* Custom report builder
* Export formats (CSV, PDF)

Reports must never break transactional integrity.

---

Reports must never break transactional integrity.

---

# MODULAR ARCHITECTURE KNOWLEDGE

## Modular Monolith Pattern

A modular monolith organizes a single deployable application into strongly bounded modules that can be independently replaced, upgraded, or extracted into microservices without breaking other modules.

Key principles:
* Each module owns its domain, infrastructure, and interface layers
* Modules communicate only via contracts (interfaces) or domain events — never direct instantiation
* No cross-module query builder calls
* Module boundaries are enforced at the code level

## Module Structure (Laravel)

Standard layout per module:

```
Modules/{ModuleName}/
├── Application/       # Use cases, commands, queries, DTOs, handlers
├── Domain/            # Entities, value objects, domain events, repository contracts
├── Infrastructure/    # Repository implementations, Eloquent models, migrations, adapters
├── Interfaces/        # HTTP controllers, API resources, form requests, console commands
├── Providers/         # Service provider, event bindings, route registration
├── Tests/             # Unit, feature, integration, tenant isolation tests
├── module.json        # Module manifest (name, version, dependencies)
└── README.md          # Module documentation
```

## Module Registration (nWidart/laravel-modules)

Using `nwidart/laravel-modules` for module discovery:

* Modules auto-register via `ModuleServiceProvider`
* Each module has its own `ServiceProvider` bootstrapped by the framework
* Module routes, migrations, views, translations, and configurations are loaded from within the module directory
* Dependency graph validation ensures no circular dependencies between modules

## Module Manifest (module.json)

```json
{
  "name": "Inventory",
  "alias": "inventory",
  "version": "1.0.0",
  "requires": ["Core", "Product"],
  "providers": ["Modules\\Inventory\\Providers\\InventoryServiceProvider"],
  "aliases": {}
}
```

## Inter-Module Communication

Allowed patterns:
1. **Contracts / Interfaces** — Inject a shared interface; implementation in one module, consumer in another
2. **Domain Events** — Emit an event from Module A; Module B listens via an event listener
3. **Shared Kernel** — Place reusable value objects and contracts in the Core module

Prohibited:
* Direct Eloquent model imports across module boundaries
* Direct query builder calls in other modules
* Shared mutable state between modules

---

# LARAVEL IMPLEMENTATION PATTERNS

## Pipeline Pattern

Laravel's Pipeline helper chains a payload through a series of stages (pipes). Each pipe receives the payload and a `$next` closure.

```php
$result = app(Pipeline::class)
    ->send($order)
    ->through([
        ValidateInventoryPipe::class,
        ReserveStockPipe::class,
        PostAccountingEntryPipe::class,
        SendConfirmationPipe::class,
    ])
    ->thenReturn();
```

Use in ERP for:
* Order processing pipelines
* Invoice approval chains
* Validation chains before financial posting
* Multi-step import workflows

## Service Provider Pattern

Each module registers bindings, event listeners, routes, migrations, and translations through its `ServiceProvider`:

```php
public function register(): void
{
    $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
}

public function boot(): void
{
    $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
    $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'product');
}
```

## Repository Pattern

Repositories abstract persistence from the domain. Domain entities and use cases depend on repository interfaces defined in the Domain layer; implementations live in Infrastructure.

```php
// Domain/Contracts/ProductRepositoryInterface.php
interface ProductRepositoryInterface {
    public function findById(int $id): ?Product;
    public function save(Product $product): void;
    public function delete(int $id): void;
}

// Infrastructure/Repositories/ProductRepository.php
class ProductRepository implements ProductRepositoryInterface { ... }
```

## Command / Handler Pattern (CQRS-lite)

Separate write (Command) from read (Query) operations:

* `CreateProductCommand` — carries intent and validated data
* `CreateProductHandler` — executes the use case, calls repository, dispatches events
* `GetProductQuery` — read-side query, returns DTO or resource

## BCMath for Financial Precision

All monetary and quantity arithmetic must use PHP's BCMath extension:

```php
// Correct
$total = bcmul($unitPrice, $quantity, 4);
$tax   = bcmul($total, bcdiv($taxRate, '100', 6), 4);
$grand = bcadd($total, $tax, 4);

// Prohibited
$total = $unitPrice * $quantity; // floating-point — forbidden
```

BCMath Object API (PHP 8.4+):
```php
use BcMath\Number;
$total = new Number($unitPrice) * new Number($quantity);
```

Scale (decimal places) must be fixed per domain:
* Money: 4 decimal places internally, 2 for display
* Quantity: 4 decimal places
* Tax rates: 6 decimal places
* Final rounding: deterministic (HALF_UP)

## Database Transactions

Wrap all multi-step writes in explicit transactions:

```php
DB::transaction(function () use ($command): void {
    $order = $this->orderRepository->create($command);
    $this->inventoryRepository->reserveStock($command->lines);
    $this->accountingRepository->postEntry($command->journalEntry);
    event(new OrderCreated($order));
});
```

* Use `DB::transaction()` for automatic rollback on exception
* Nested transactions use savepoints automatically
* Always set `lockForUpdate()` on rows susceptible to race conditions before deducting

## Pessimistic Locking

For concurrent stock deduction:

```php
$stock = StockLedgerEntry::where('product_id', $productId)
    ->where('warehouse_id', $warehouseId)
    ->lockForUpdate()
    ->first();

// Deduct safely inside transaction
```

## Optimistic Locking

For non-critical updates with version tracking:

```php
// Use Eloquent's version column
$model->save(); // throws StaleModelLockingException if version changed
```

## Idempotency

All state-changing API endpoints must support idempotency keys:

```php
// Client sends: X-Idempotency-Key: {uuid}
// Server checks cache/DB before processing
// Returns stored result on duplicate key
```

---

# MULTI-GUARD AUTHENTICATION

## Guards Overview

Laravel supports multiple authentication guards allowing different user types to authenticate via different mechanisms:

```php
// config/auth.php
'guards' => [
    'api'         => ['driver' => 'jwt',     'provider' => 'users'],
    'admin'       => ['driver' => 'jwt',     'provider' => 'admins'],
    'pos'         => ['driver' => 'session', 'provider' => 'pos_users'],
]
```

## JWT Per User × Device × Organisation

Each JWT token carries:
* `sub` — user UUID
* `tenant_id` — tenant identifier
* `org_id` — organisation scope
* `device_id` — device fingerprint (for revocation)
* `permissions` — cached RBAC claims

Token lifecycle:
1. Login → issue access token (short TTL) + refresh token (long TTL)
2. Access token expires → use refresh token to rotate
3. Logout → blacklist token pair
4. Device revoked → blacklist all tokens for that device

## Tenant Resolution

Tenants are resolved from:
1. JWT `tenant_id` claim (primary)
2. `X-Tenant-ID` header (fallback)
3. Subdomain extraction (optional)

Tenant middleware applies global scope to all queries after resolution.

---

# LOCALIZATION & INTERNATIONALIZATION

## Translation Architecture

All user-facing strings must be stored in language files or translatable model fields:

```
resources/lang/
├── en/
│   ├── products.php
│   └── validation.php
└── ar/
    ├── products.php
    └── validation.php
```

Module-scoped translations:
```php
// Loaded in ServiceProvider
$this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'product');

// Used in code
__('product::products.created_successfully')
```

## Polymorphic Translatable Models

Use spatie/laravel-translatable or a custom polymorphic pattern:

```php
// Product model with translatable fields
use Spatie\Translatable\HasTranslations;

class Product extends Model {
    use HasTranslations;
    public $translatable = ['name', 'description'];
}

// Usage
$product->setTranslation('name', 'en', 'Widget');
$product->setTranslation('name', 'ar', 'ويدجت');
$product->getTranslation('name', 'ar'); // ويدجت
```

Alternative: Astrotomic/laravel-translatable for relationship-based translation tables.

## Locale Middleware

Detect and apply locale from:
1. `Accept-Language` header
2. User profile locale preference
3. Tenant default locale
4. System fallback locale

```php
app()->setLocale($resolvedLocale);
```

## RTL Support

UI must support right-to-left languages (Arabic, Hebrew, Urdu):
* CSS `dir="rtl"` on HTML root for RTL locales
* AdminLTE and TailAdmin both provide RTL variants
* TailwindCSS supports `rtl:` prefix variants

---

# BARCODE / QR CODE / GS1 STANDARDS

## Barcode Types

| Standard | Use Case | Structure |
|---|---|---|
| EAN-13 | Retail product identification | 12 digits + 1 check digit |
| Code 128 | Logistics, variable-length alphanumeric | Auto-selected subsets A/B/C |
| QR Code | URLs, product info, digital menus | 2D matrix, up to 3KB |
| Data Matrix | Small parts, electronics | 2D matrix, compact |
| ITF-14 | Carton/outer case labeling | 14 digits, GTIN-14 |

## GS1 Standards

GS1 provides globally unique identifiers:

* **GTIN** (Global Trade Item Number) — identifies products
  * GTIN-8, GTIN-12 (UPC-A), GTIN-13 (EAN-13), GTIN-14
* **GLN** (Global Location Number) — identifies locations
* **SSCC** (Serial Shipping Container Code) — identifies logistics units
* **GS1-128** — application identifier barcodes for lot, expiry, serial

GS1 Application Identifiers (AIs):
* `(01)` — GTIN
* `(10)` — Lot/Batch Number
* `(17)` — Expiry Date
* `(21)` — Serial Number

## EAN-13 Check Digit Algorithm

```php
function ean13CheckDigit(string $digits12): int
{
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int)$digits12[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    return (10 - ($sum % 10)) % 10;
}
```

## QR Code Generation

QR codes encode structured data for:
* Product URLs
* Stocktake scanning
* Customer loyalty cards
* Mobile payment deep links

---

# E-COMMERCE INTEGRATION

## E-commerce Types

| Type | Description |
|---|---|
| B2C | Business to consumer — retail storefront |
| B2B | Business to business — wholesale portal |
| D2C | Direct to consumer — brand sells without intermediary |
| Headless | API-only backend, decoupled frontend |
| Marketplace | Multi-vendor platform |

## Headless Commerce

An API-first approach where the commerce engine (ERP/backend) is completely decoupled from the presentation layer. The ERP exposes:
* Product catalog API
* Inventory availability API
* Pricing / promotion API
* Order management API
* Customer account API

Frontend (React, mobile app, kiosk) consumes these APIs independently.

## WooCommerce Sync Pattern

When integrating with WooCommerce:

1. **Product Sync** — Push products from ERP to WooCommerce via REST API; track `wc_product_id` in ERP
2. **Order Pull** — Webhook from WooCommerce `woocommerce_new_order` → ERP creates Sales Order
3. **Stock Push** — On ERP stock change, push updated stock level to WooCommerce via `PUT /products/{id}`
4. **Customer Sync** — Bidirectional customer/contact sync with deduplication

WooCommerce REST API endpoints used:
```
GET  /wp-json/wc/v3/products
POST /wp-json/wc/v3/products
PUT  /wp-json/wc/v3/products/{id}
GET  /wp-json/wc/v3/orders
PUT  /wp-json/wc/v3/products/{id} (stock update)
```

## E-commerce API Compatibility Layer

The ERP must expose e-commerce compatible endpoints:
```
GET  /api/v1/catalog/products           # Public product listing
GET  /api/v1/catalog/products/{slug}    # Single product with variants/pricing
GET  /api/v1/catalog/categories         # Category tree
POST /api/v1/storefront/cart            # Add to cart (reservation)
POST /api/v1/storefront/checkout        # Place order
GET  /api/v1/storefront/orders/{id}     # Order status
```

---

# POINT OF SALE (POS) ARCHITECTURE

## POS Design Principles

A production-grade POS terminal must support:

* **Offline-first** — operates without internet connectivity
* **Local transaction queue** — orders stored locally until sync
* **Sync reconciliation** — resolves conflicts when connectivity restored
* **Cash drawer control** — open/close events logged
* **Receipt templating** — configurable receipt layouts
* **Split payments** — cash + card + store credit in one transaction
* **Draft / hold** — park transactions and recall
* **Loyalty & discounts** — apply at point of sale
* **Shift management** — open/close register with cash count

## POS Transaction Flow

```
Open Register → Session Start
  ↓
Scan / Add Product Lines
  ↓
Apply Discounts / Coupons
  ↓
Calculate Taxes
  ↓
Choose Payment Method(s)
  ↓
Process Payment
  ↓
Deduct Stock (Pessimistic Lock)
  ↓
Post Accounting Entry
  ↓
Print / Email Receipt
  ↓
Close Register → Session End → Z-Report
```

## Offline Sync Architecture

```
POS Terminal (Browser/App)
  ├── IndexedDB / LocalStorage — offline transaction queue
  ├── Service Worker — intercepts network requests
  └── Sync Worker — uploads queue when online
        ↓
ERP Backend
  ├── Idempotency key validation (prevent double-posting)
  ├── Conflict resolution (timestamp-wins or manual review)
  └── Stock reconciliation
```

## Z-Report (End of Day)

The Z-Report summarizes:
* Total sales by payment method
* Total discounts applied
* Total tax collected
* Net cash in drawer
* Discrepancies vs expected
* Signed off by manager

---

# WAREHOUSE MANAGEMENT SYSTEM (WMS)

## WMS Core Concepts

| Concept | Description |
|---|---|
| Warehouse | Physical facility |
| Zone | Logical area within warehouse (receiving, picking, dispatch) |
| Aisle | Row of rack systems |
| Rack | Vertical shelf unit |
| Bin / Location | Individual shelf position — smallest addressable unit |
| Lot / Batch | Group of same product received together |
| Serial Number | Unique identifier for individual item |
| Expiry Date | Best-before or use-by date |

## Stock Ledger Entries

All stock movements are recorded as immutable ledger entries:

| Entry Type | Effect |
|---|---|
| PURCHASE_RECEIPT | + stock (inbound) |
| SALES_SHIPMENT | − stock (outbound) |
| INTERNAL_TRANSFER_OUT | − stock (source location) |
| INTERNAL_TRANSFER_IN | + stock (destination location) |
| ADJUSTMENT_POSITIVE | + stock (counted more than system) |
| ADJUSTMENT_NEGATIVE | − stock (counted less than system) |
| RETURN_FROM_CUSTOMER | + stock (returned inbound) |
| RETURN_TO_VENDOR | − stock (returned outbound) |
| MANUFACTURING_CONSUMPTION | − stock (components used) |
| MANUFACTURING_OUTPUT | + stock (finished goods produced) |

## Cycle Counting

Instead of full annual stocktakes:
* Random sample of bin locations counted daily
* Discrepancies flagged for investigation
* Corrections posted as adjustment entries
* ABC classification drives frequency: A items counted monthly, B quarterly, C annually

## FIFO Costing Implementation

For FIFO (First-In First-Out):
1. Each purchase receipt records `unit_cost` at time of receipt
2. On stock deduction, consume oldest-received units first
3. Cost of Goods Sold (COGS) = cost of oldest units consumed
4. Remaining inventory valued at most recent receipt cost

---

# BUSINESS PROCESS MODELING

## BPMN (Business Process Model and Notation)

BPMN defines standard notation for process modeling:

* **Start Event** — process trigger (message, timer, signal)
* **End Event** — process termination
* **Task** — atomic unit of work (User Task, Service Task, Script Task)
* **Gateway** — decision point (Exclusive, Parallel, Inclusive)
* **Sequence Flow** — transition between elements
* **Message Flow** — communication between pools
* **Pool / Lane** — represents participants or departments

ERP flows modeled as BPMN:
* Sales Order lifecycle (Quotation → Delivery → Invoice → Payment)
* Procurement cycle (PR → RFQ → PO → GR → Bill → Payment)
* Manufacturing order (BOM → Production → QC → Stock)
* Approval workflows (multi-level, escalation on SLA breach)

## Workflow State Machine

```
States:    DRAFT → PENDING_APPROVAL → APPROVED → IN_PROGRESS → COMPLETED
                                   ↘ REJECTED ↗
                                   
Transitions are guarded by:
  - Authorization policies (who can approve)
  - Business rules (minimum order value, credit limit check)
  - Data validation (all required fields filled)
```

## Gap Analysis

Gap analysis compares current state vs. desired state:

| Dimension | Current State | Target State | Gap |
|---|---|---|---|
| Feature | Manual Excel-based ordering | Automated PO generation | Procurement automation |
| Process | No approval workflow | 3-level approval chain | Workflow engine |
| Integration | No e-commerce sync | Real-time WooCommerce sync | API integration layer |

Gap analysis drives the implementation roadmap and module prioritization.

---

# FRONTEND ARCHITECTURE

## Technology Stack

* **React (LTS)** — component-based UI, JSX, hooks
* **TailwindCSS** — utility-first CSS framework
* **AdminLTE** — admin dashboard template (Bootstrap-based)
* **TailAdmin** — Tailwind-based admin dashboard template

## AdminLTE Integration

AdminLTE provides:
* Pre-built admin dashboard layout
* Sidebar navigation
* Data tables
* Form components
* Cards and widgets
* React port available via madewithreact.com/reactjs-adminlte

## TailAdmin Integration

TailAdmin provides a modern Tailwind + React dashboard:
* TypeScript-ready
* Dark mode support
* RTL support
* Component library: tables, charts, forms, modals

## API Client Pattern

Frontend communicates with ERP backend via versioned REST API:

```typescript
// Standard response envelope
interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T | null;
  errors: Record<string, string[]> | null;
  meta?: PaginationMeta;
}

// Axios instance with interceptors
const apiClient = axios.create({ baseURL: '/api/v1' });
apiClient.interceptors.request.use(config => {
  config.headers.Authorization = `Bearer ${getToken()}`;
  config.headers['X-Tenant-ID'] = getTenantId();
  return config;
});
```

## State Management

For ERP frontend state:
* **Server state** — React Query / TanStack Query (caching, invalidation)
* **Local state** — React useState / useReducer
* **Form state** — React Hook Form with Zod validation
* **Global state** — Zustand (lightweight) for auth, tenant context

---

# OPENAPI / SWAGGER DOCUMENTATION

## L5-Swagger Integration

Using `darkaonline/l5-swagger` for Laravel:

```php
// Controller-level annotation
/**
 * @OA\Get(
 *     path="/api/v1/products",
 *     summary="List products",
 *     tags={"Products"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success",
 *         @OA\JsonContent(ref="#/components/schemas/ProductCollection")
 *     )
 * )
 */
public function index(Request $request): JsonResponse { ... }
```

## Standard Response Envelope

Every API response must follow:

```json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": { ... },
  "errors": null,
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 234,
    "last_page": 16
  }
}
```

Error response:
```json
{
  "success": false,
  "message": "Validation failed",
  "data": null,
  "errors": {
    "sku": ["The SKU has already been taken."],
    "price": ["The price must be greater than 0."]
  }
}
```

## OpenAPI Schema Definitions

Define reusable schemas in a central location:

```php
/**
 * @OA\Schema(
 *   schema="Product",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="sku", type="string"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="price", type="string", example="19.9900"),
 *   @OA\Property(property="type", type="string", enum={"physical","service","digital"})
 * )
 */
```

---

# LARAVEL PACKAGES & ECOSYSTEM

## Package Development

When building reusable Laravel packages:

1. Create a `ServiceProvider` that registers bindings, routes, migrations, views
2. Use `composer.json` `extra.laravel.providers` for auto-discovery
3. Publish configuration: `php artisan vendor:publish --tag=package-config`
4. Publish migrations: `php artisan vendor:publish --tag=package-migrations`
5. Write tests using Laravel's `TestCase` and `RefreshDatabase`

## Key Packages Used

| Package | Purpose |
|---|---|
| `nwidart/laravel-modules` | Module system with auto-discovery |
| `tymon/jwt-auth` | JWT authentication |
| `darkaonline/l5-swagger` | OpenAPI documentation generation |
| `spatie/laravel-permission` | RBAC roles & permissions |
| `spatie/laravel-translatable` | Translatable model attributes |
| `spatie/laravel-multitenancy` | Multi-tenancy infrastructure |
| `league/flysystem-aws-s3-v3` | S3-compatible cloud file storage |
| `predis/predis` | Redis client for caching/queues |

## Laravel Filesystem & File Upload

Secure file uploads:

```php
// Validate file type and size
$request->validate([
    'image' => 'required|image|mimes:jpeg,png,webp|max:2048',
    'document' => 'required|file|mimes:pdf|max:10240',
]);

// Store with hashed filename (prevents path traversal)
$path = $request->file('image')->store("tenants/{$tenantId}/products", 'private');

// Generate time-limited signed URL for access
$url = Storage::temporaryUrl($path, now()->addMinutes(30));
```

Storage must be:
* Tenant-scoped paths
* Private by default
* Accessible only via signed URLs
* Validated for type and content (not just extension)

## Laravel Process API

For background tasks requiring shell execution:

```php
use Illuminate\Support\Facades\Process;

$result = Process::run('php artisan queue:work --stop-when-empty');

if ($result->failed()) {
    Log::error('Queue worker failed', ['output' => $result->errorOutput()]);
}
```

---

# SECURITY KNOWLEDGE (EXPANDED)

## CSRF Protection

All web routes must use CSRF tokens. API routes using JWT are stateless and exempt from CSRF (bearer token provides equivalent protection).

## XSS Prevention

* Escape all output in Blade: `{{ $var }}` (not `{!! $var !!}`)
* Sanitize rich text inputs using HTMLPurifier
* Set `Content-Security-Policy` headers
* Use `httpOnly` and `SameSite=Strict` cookies

## SQL Injection Prevention

* Always use Eloquent ORM or parameterized query builder
* Never interpolate user input into raw SQL
* Use `whereRaw('column = ?', [$userInput])` if raw is unavoidable

## Rate Limiting

```php
// Per tenant, per endpoint
Route::middleware(['throttle:api,60,1'])->group(...);

// Custom throttle using tenant ID
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->tenant_id ?: $request->ip());
});
```

## Audit Logging

Every write operation must log:
* Who (user ID, tenant ID)
* What (model, action: create/update/delete)
* When (timestamp with timezone)
* Before state (old values)
* After state (new values)
* IP address
* Request ID

Audit logs are append-only and must never be edited or deleted.

## Token Rotation

JWT refresh token rotation:
1. Refresh token is single-use
2. On use, old refresh token is invalidated, new pair issued
3. Simultaneous use of same refresh token triggers security alert and full revocation

---

# CORE SYSTEM GUARANTEES

The system must guarantee:

* Tenant isolation
* Financial correctness
* Transactional consistency
* Replaceable modules
* Deterministic calculations
* Audit safety
* Horizontal scalability
* Extensibility without refactor debt

---

# STRATEGIC DESIGN GOAL

Build not just an ERP.

Build:

* A configurable ERP framework
* A reusable SaaS engine
* A domain-driven enterprise core
* A plugin-ready platform
* A long-term maintainable system

---

# REFERENCES

These references provide guidance on modular design, Laravel best practices, multi-tenancy, ERP/CRM design, and other principles relevant to this repository:

- https://blog.cleancoder.com/atom.xml
- https://en.wikipedia.org/wiki/Modular_design
- https://en.wikipedia.org/wiki/Plug-in_(computing)
- https://laravel.com/blog/building-a-multi-tenant-architecture-platform-to-scale-the-emmys
- https://en.wikipedia.org/wiki/Enterprise_resource_planning
- https://dev.to/rafaelogic/building-a-polymorphic-translatable-model-in-laravel-with-autoloaded-translations-3d99
- https://sevalla.com/blog/building-modular-systems-laravel
- https://github.com/laravel/laravel
- https://laravel.com/docs/12.x/packages
- https://swagger.io
- https://en.wikipedia.org/wiki/SOLID
- https://laravel.com/docs/12.x/filesystem
- https://laravel-news.com/uploading-files-laravel
- https://adminlte.io
- https://tailwindcss.com
- https://dev.to/bhaidar/understanding-database-locking-and-concurrency-in-laravel-a-deep-dive-2k4m
- https://laravel-news.com/managing-data-races-with-pessimistic-locking-in-laravel
- https://dev.to/tegos/pessimistic-optimistic-locking-in-laravel-23dk
- https://dev.to/takeshiyu/handling-decimal-calculations-in-php-84-with-the-new-bcmath-object-api-442j
- https://dev.to/keljtanoski/modular-laravel-3dkf
- https://laravel.com/docs/12.x/processes
- https://laravel.com/docs/12.x/helpers#pipeline
- https://dev.to/preciousaang/multi-guard-authentication-with-laravel-12-1jg3
- https://laravel.com/docs/12.x/authentication
- https://laravel.com/docs/12.x/localization
- https://www.laravelpackage.com
- https://laravel-news.com/building-your-own-laravel-packages
- https://freek.dev/1567-pragmatically-testing-multi-guard-authentication-in-laravel
- https://laravel-news.com/laravel-gates-policies-guards-explained
- https://dev.to/codeanddeploy/how-to-create-a-custom-dynamic-middleware-for-spatie-laravel-permission-2a08
- https://laravel.com/docs/12.x/authorization
- https://en.wikipedia.org/wiki/Inventory
- https://en.wikipedia.org/wiki/Inventory_management_software
- https://en.wikipedia.org/wiki/Inventory_management
- https://en.wikipedia.org/wiki/Inventory_management_(business)
- https://en.wikipedia.org/wiki/Inventory_theory
- https://en.wikipedia.org/wiki/SAP_ERP
- https://en.wikipedia.org/wiki/SAP
- https://en.wikipedia.org/wiki/E-commerce
- https://en.wikipedia.org/wiki/Types_of_e-commerce
- https://en.wikipedia.org/wiki/Headless_commerce
- https://en.wikipedia.org/wiki/Barcode
- https://en.wikipedia.org/wiki/QR_code
- https://en.wikipedia.org/wiki/GS1
- https://en.wikipedia.org/wiki/Warehouse_management_system
- https://en.wikipedia.org/wiki/Domain_inventory_pattern
- https://en.wikipedia.org/wiki/Point_of_sale
- https://www.oracle.com/apac/food-beverage/what-is-pos
- https://en.wikipedia.org/wiki/Gap_analysis
- https://en.wikipedia.org/wiki/Business_process_modeling
- https://en.wikipedia.org/wiki/Business_process
- https://en.wikipedia.org/wiki/Business_Process_Model_and_Notation
- https://en.wikipedia.org/wiki/Process_design
- https://en.wikipedia.org/wiki/Business_process_mapping
- https://en.wikipedia.org/wiki/Workflow_pattern
- https://en.wikipedia.org/wiki/Workflow
- https://en.wikipedia.org/wiki/Workflow_application
- https://www.researchgate.net/publication/279515140_Enterprise_Resource_Planning_ERP_Systems_Design_Trends_and_Deployment
- https://medium.com/@bugfreeai/key-system-design-component-design-an-inventory-system-2e2befe45844
- https://www.cockroachlabs.com/blog/inventory-management-reference-architecture
- https://www.0xkishan.com/blogs/designing-inventory-mgmt-system
- https://quickbooks.intuit.com/r/bookkeeping/complete-guide-to-double-entry-bookkeeping
- https://www.extension.iastate.edu/agdm/wholefarm/pdf/c6-33.pdf
- https://en.wikipedia.org/wiki/Double-entry_bookkeeping
- https://en.wikipedia.org/wiki/Bookkeeping
- https://github.com/DarkaOnLine/L5-Swagger
- https://medium.com/@nelsonisioma1/how-to-document-your-laravel-api-with-swagger-and-php-attributes-1564fc11c305
- https://medium.com/@harryespant/advanced-microservices-architecture-in-laravel-high-level-design-dependency-injection-repository-0e787a944e7f
- https://dev.to/programmerhasan/creating-a-microservice-architecture-with-laravel-apis-3a16
- https://laravel.com/ai/mcp
- https://laravel.com/docs/12.x/mcp
- https://github.com/L5Modular/L5Modular
- https://github.com/nWidart/laravel-modules
- https://github.com/keljtanoski/modular-laravel
- https://laravel.com/docs/12.x
- https://woocommerce.com
- https://developer.wordpress.org/plugins/intro
- https://en.wikipedia.org/wiki/WooCommerce
- https://github.com/woocommerce/woocommerce
- https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction
- https://developer.wordpress.org
- https://github.com/Astrotomic/laravel-translatable
- https://github.com/spatie/laravel-translatable
- https://oneuptime.com/blog/post/2026-02-03-laravel-multi-language/view
- https://github.com/spatie/laravel-translatable/blob/main/docs/introduction.md
- https://dev.to/abstractmusa/modular-monolith-architecture-within-laravel-communication-between-different-modules-a5
- https://oneuptime.com/blog/post/2026-02-02-laravel-database-transactions/view
- https://laravel-news.com/database-transactions
- https://github.com/spatie/laravel-multitenancy
- https://github.com/archtechx/tenancy
- https://sevalla.com/blog/mcp-server-laravel
- https://github.com/laravel/mcp
- https://tailadmin.com
- https://github.com/TailAdmin/tailadmin-free-tailwind-dashboard-template
- https://github.com/ColorlibHQ/AdminLTE
- https://adminlte.io/blog/free-react-templates
- https://madewithreact.com/reactjs-adminlte
- https://github.com/TailAdmin/free-react-tailwind-admin-dashboard?tab=readme-ov-file
- https://oneuptime.com/blog/post/2026-02-03-laravel-repository-pattern/view
- https://micro-frontends.org
- https://en.wikipedia.org/wiki/Micro_frontend
- https://single-spa.js.org/docs/microfrontends-concept
- https://nx.dev/docs/technologies/module-federation/concepts/micro-frontend-architecture
- https://semaphore.io/blog/microfrontends
- https://www.geeksforgeeks.org/blogs/what-are-micro-frontends-definition-uses-architecture
- https://microfrontend.dev
- https://bit.dev/docs/micro-frontends/react-micro-frontends
- https://medium.com/@nexckycort/from-monolith-to-microfrontends-migrating-a-legacy-react-app-to-a-modern-architecture-bd686aee0ce8
- https://blog.nashtechglobal.com/the-power-of-react-micro-frontend
- https://github.com/miteshtagadiya/microfrontend-react
- https://medium.com/@ignatovich.dm/micro-frontend-architectures-in-react-benefits-and-implementation-strategies-5a8bd5f66769
- https://github.com/nrwl/nx
- https://github.com/neuland/micro-frontends
- https://laraveldaily.com/post/traits-laravel-eloquent-examples
- https://dcblog.dev/enhancing-laravel-applications-with-traits-a-step-by-step-guide
- https://laravel.com/docs/12.x/migrations
- https://laravel.com/docs/12.x/eloquent-relationships
- https://laravel-news.com/effective-eloquent
- https://dev.to/rocksheep/cleaner-models-with-laravel-eloquent-builders-12h4
- https://dev.to/abrardev99/pipeline-pattern-in-laravel-278p
- https://jordandalton.com/articles/laravel-pipelines-transforming-your-code-into-a-flow-of-efficiency
- https://medium.com/@harrisrafto/streamlining-data-processing-with-laravels-pipeline-pattern-8f939ee68435
- https://marcelwagner.dev/blog/posts/what-are-laravel-pipelines
- https://jahidhassan.hashnode.dev/how-i-simplified-my-laravel-filters-using-the-pipeline-pattern-with-real-examples
- https://laracasts.com/discuss/channels/eloquent/create-a-custom-relationship-method
- https://stackoverflow.com/questions/39213022/custom-laravel-relations
- https://api.laravel.com/docs/12.x/index.html
- https://medium.com/coding-skills/clean-code-101-meaningful-names-and-functions-bf450456d90c
- https://devopedia.org/naming-conventions
- https://www.oracle.com/java/technologies/javase/codeconventions-namingconventions.html
- https://www.freecodecamp.org/news/how-to-write-better-variable-names
- https://en.wikipedia.org/wiki/Naming_convention_(programming)
- https://www.netsuite.com/portal/resource/articles/inventory-management/what-are-inventory-management-controls.shtml
- https://www.finaleinventory.com/inventory-management/retail-inventory-management-15-best-practices-for-2024-ecommerce
- https://modula.us/blog/warehouse-inventory-management
- https://sell.amazon.com/learn/inventory-management
- https://www.sap.com/resources/inventory-management
- https://www.camcode.com/blog/warehouse-operations-best-practices
- https://www.ascm.org/ascm-insights/8-kpis-for-an-efficient-warehouse
- https://ascsoftware.com/blog/good-warehousing-practices-in-the-pharmaceutical-industry
- https://www.buchananlogistics.com/resources/company-news-and-blogs/blogs/utilizing-the-7-cs-systems-of-logistics-and-supply-chain-management
- https://axacute.com/blog/5-key-warehouse-processes
- https://www.conger.com/warehouse-5s
- https://navata.com/cms/1pl-2pl-3pl-4pl-5pl
- https://www.researchgate.net/publication/329038196_Design_of_Automated_Warehouse_Management_System
- https://www.researchgate.net/publication/377780666_DESIGNING_AN_INVENTORY_MANAGEMENT_SYSTEM_USING_DATA_MINING_TECHNIQUES

---

# FINAL DIRECTIVE

This system must evolve into a:

* Fully modular
* Fully pluggable
* Fully configurable
* Fully tenant-isolated
* Fully enterprise-grade
* Financially precise
* Horizontally scalable
* Vertically scalable
* Audit-safe ERP/CRM SaaS platform

Zero regression tolerance.
Zero architectural violations.
Zero technical debt acceptance.

This contract is binding for all contributors and AI agents.
