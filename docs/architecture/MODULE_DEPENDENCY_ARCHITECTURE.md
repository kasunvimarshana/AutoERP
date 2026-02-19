# Module Dependency Architecture

## Overview

This document provides a comprehensive view of the module dependency architecture for the Enterprise ERP/CRM SaaS Platform. The system consists of 16 loosely-coupled, plugin-style modules organized in a strict priority hierarchy to prevent circular dependencies.

## Validation Commands

```bash
# Validate module dependencies (no circular deps, correct priorities)
php artisan modules:validate-dependencies

# Health check all modules
php artisan modules:health-check

# Health check specific module
php artisan modules:health-check Core --details
```

## Architecture Statistics

| Metric | Value |
|--------|-------|
| **Total Modules** | 16 |
| **Total Dependencies** | 63 |
| **Average Dependencies per Module** | 3.94 |
| **Maximum Dependency Depth** | 8 |
| **Circular Dependencies** | 0 ✅ |

## Module Priority Hierarchy

Modules are loaded in strict priority order (1-14), where lower numbers are loaded first. Dependencies must always have lower priority than dependent modules.

```
Priority 1  (Foundation)     : Core
Priority 2  (Multi-tenancy)  : Tenant
Priority 3  (Authentication) : Auth
Priority 4  (Auditing)       : Audit
Priority 5  (Catalog)        : Product
Priority 6  (Pricing)        : Pricing
Priority 7  (CRM)            : CRM
Priority 8  (Sales)          : Sales
Priority 9  (Procurement)    : Purchase
Priority 10 (Inventory)      : Inventory
Priority 11 (Accounting)     : Accounting
Priority 12 (Services)       : Notification, Billing
Priority 13 (Advanced)       : Reporting, Document
Priority 14 (Automation)     : Workflow
```

## Dependency Graph

### Visual Representation

```
Core (1) ─┐
          │
Tenant (2)├─── Auth (3) ─┐
          │              │
          │         Audit (4) ───────────┐
          │              │               │
          │         Product (5) ─┐       │
          │              │       │       │
          │         Pricing (6) ─┤       │
          │              │       │       │
          │         CRM (7) ─────┤       │
          │              │       │       │
          │         Sales (8) ───┼───────┤
          │              │       │       │
          │         Purchase (9) ┤       │
          │              │       │       │
          │         Inventory (10)       │
          │              │               │
          │         Accounting (11)      │
          │              │               │
          │         Notification (12) ───┤
          │              │               │
          │         Billing (12) ────────┤
          │              │               │
          │         Reporting (13) ──────┤
          │              │               │
          │         Document (13) ───────┤
          │              │               │
          │         Workflow (14) ───────┘
```

## Detailed Module Dependencies

### Layer 1: Foundation (Priority 1)

#### Core
- **Priority**: 1
- **Dependencies**: None
- **Provides**: 
  - ModuleInterface
  - MathHelper
  - TransactionHelper
  - Exception hierarchy (27+ exceptions)
  - BaseRepository pattern
- **Description**: Foundation module providing base infrastructure for all other modules

### Layer 2: Multi-Tenancy (Priority 2)

#### Tenant
- **Priority**: 2
- **Dependencies**: Core
- **Provides**:
  - TenantContext
  - TenantScoped trait
  - Organization hierarchy support
- **Description**: Multi-tenancy infrastructure with hierarchical organizations

### Layer 3: Security (Priority 3)

#### Auth
- **Priority**: 3
- **Dependencies**: Core, Tenant
- **Provides**:
  - JwtTokenService
  - TokenServiceInterface
  - Multi-device authentication
  - RBAC/ABAC policies
- **Description**: Stateless JWT authentication with multi-guard support

### Layer 4: Auditing (Priority 4)

#### Audit
- **Priority**: 4
- **Dependencies**: Core, Tenant, Auth
- **Provides**:
  - AuditService
  - Auditable trait
  - Event listeners (6)
- **Description**: Comprehensive audit logging for all operations

### Layer 5: Product Catalog (Priority 5)

#### Product
- **Priority**: 5
- **Dependencies**: Core, Tenant, Audit
- **Provides**:
  - ProductService
  - Product, ProductCategory, ProductBundle models
  - Unit conversions
- **Description**: Product catalog with goods, services, bundles, and composites

### Layer 6: Pricing (Priority 6)

#### Pricing
- **Priority**: 6
- **Dependencies**: Core, Tenant, Product
- **Provides**:
  - PricingService
  - PricingEngineInterface
  - 6 pricing engines (flat, %, tiered, volume, time-based, customer-based)
- **Description**: Extensible pricing engines with rule-based calculations

### Layer 7: Customer Relations (Priority 7)

#### CRM
- **Priority**: 7
- **Dependencies**: Core, Tenant, Auth, Audit
- **Provides**:
  - CustomerRepository
  - LeadRepository
  - OpportunityRepository
  - LeadConversionService
  - OpportunityService
- **Description**: Customer relationship management (leads, opportunities, contacts)

### Layer 8: Sales Operations (Priority 8)

#### Sales
- **Priority**: 8
- **Dependencies**: Core, Tenant, Auth, Audit, Product, Pricing, CRM
- **Provides**:
  - QuotationRepository, OrderRepository, InvoiceRepository
  - QuotationService, OrderService, InvoiceService
- **Description**: Quote-to-Cash workflow (quotations → orders → invoices)

### Layer 9: Procurement (Priority 9)

#### Purchase
- **Priority**: 9
- **Dependencies**: Core, Tenant, Auth, Audit, Product, Pricing
- **Provides**:
  - VendorRepository, PurchaseOrderRepository
  - GoodsReceiptRepository, BillRepository
  - VendorService, PurchaseOrderService
  - GoodsReceiptService, BillService
- **Description**: Procure-to-Pay workflow (PO → receipts → bills)

### Layer 10: Warehouse (Priority 10)

#### Inventory
- **Priority**: 10
- **Dependencies**: Core, Tenant, Auth, Audit, Product, Sales, Purchase
- **Provides**:
  - WarehouseRepository, StockItemRepository
  - StockMovementRepository, StockCountRepository
  - WarehouseService, StockMovementService
  - InventoryValuationService, StockCountService
  - ReorderService, SerialNumberService
- **Description**: Multi-warehouse inventory with stock tracking and valuation (FIFO/LIFO/Weighted Average)

### Layer 11: Financial (Priority 11)

#### Accounting
- **Priority**: 11
- **Dependencies**: Core, Tenant, Auth, Audit, Sales, Purchase, Inventory
- **Provides**:
  - AccountRepository, JournalEntryRepository
  - FiscalPeriodRepository
  - AccountingService, ChartOfAccountsService
  - GeneralLedgerService, TrialBalanceService
  - FinancialStatementService
- **Description**: Double-entry bookkeeping with financial statements

### Layer 12: Services (Priority 12)

#### Notification
- **Priority**: 12
- **Dependencies**: Core, Tenant, Auth, Audit
- **Provides**:
  - NotificationRepository, TemplateRepository
  - ChannelRepository, NotificationLogRepository
  - NotificationService, TemplateService
  - ChannelService, NotificationDeliveryService
- **Description**: Multi-channel notifications (Email/SMS/Push) with integrations (Twilio/SNS/FCM)

#### Billing
- **Priority**: 12
- **Dependencies**: Core, Tenant, Auth, Audit
- **Provides**:
  - PlanRepository, SubscriptionRepository
  - SubscriptionPaymentRepository
  - SubscriptionService, PaymentService
  - BillingCalculationService, UsageTrackingService
- **Description**: SaaS subscriptions with payment gateway integrations (Stripe/PayPal/Razorpay)

### Layer 13: Advanced (Priority 13)

#### Reporting
- **Priority**: 13
- **Dependencies**: Core, Tenant, Auth, Audit
- **Provides**:
  - ReportRepository, DashboardRepository, WidgetRepository
  - ReportBuilder, ExportService, AnalyticsService
- **Description**: Dashboards and analytics with export (CSV/JSON/PDF)

#### Document
- **Priority**: 13
- **Dependencies**: Core, Tenant, Auth, Audit
- **Provides**:
  - DocumentRepository, FolderRepository, VersionRepository
  - DocumentStorageService, VersionControlService, SharingService
- **Description**: Document management with version control and sharing

### Layer 14: Automation (Priority 14)

#### Workflow
- **Priority**: 14
- **Dependencies**: Core, Tenant, Auth, Audit
- **Provides**:
  - WorkflowRepository, WorkflowInstanceRepository
  - ApprovalRepository
  - WorkflowEngine, WorkflowExecutor, ApprovalService
- **Description**: Business process automation with multi-level approvals

## Dependency Chains

### Longest Dependency Chain (Depth: 8)
```
Accounting → Inventory → Sales → CRM → Audit → Auth → Tenant → Core
```

### Critical Path Modules

These modules have the most dependents:

1. **Core** (15 dependents) - Used by all modules
2. **Tenant** (15 dependents) - Required for multi-tenancy
3. **Auth** (13 dependents) - Required for authentication
4. **Audit** (13 dependents) - Required for audit logging
5. **Product** (5 dependents) - Used by Sales, Purchase, Inventory, Pricing, Accounting

## Module Isolation Principles

### 1. No Circular Dependencies
- Enforced via strict priority ordering
- Validated by `modules:validate-dependencies` command
- Dependencies must have lower priority than dependent modules

### 2. No Direct Cross-Module Imports
- Modules communicate via:
  - Events (95+ events)
  - Service contracts (interfaces)
  - API calls
- Never: `use Modules\SalesModule\*` in PurchaseModule

### 3. Event-Driven Integration
```php
// Sales module fires event
event(new OrderCreated($order));

// Inventory module listens
class OrderCreatedListener {
    public function handle(OrderCreated $event) {
        // Reserve stock
    }
}
```

### 4. Service Contracts
```php
// Core defines interface
interface PricingEngineInterface {
    public function calculate(Product $product, Customer $customer): Money;
}

// Pricing module implements
class TieredPricingEngine implements PricingEngineInterface { ... }
```

## Configuration Management

### Module Configuration (config/modules.php)
```php
'Sales' => [
    'enabled' => env('MODULE_SALES_ENABLED', true),
    'priority' => 8,
    'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit', 'Product', 'Pricing', 'CRM'],
    'provides' => ['QuotationRepository', 'OrderService', ...],
]
```

### Module-Specific Config (modules/Sales/Config/sales.php)
```php
return [
    'quotation_validity_days' => env('SALES_QUOTATION_VALIDITY_DAYS', 30),
    'order_auto_confirm' => env('SALES_ORDER_AUTO_CONFIRM', false),
    'invoice_payment_terms' => env('SALES_INVOICE_PAYMENT_TERMS', 30),
];
```

### Environment Variables (.env)
```env
MODULE_SALES_ENABLED=true
SALES_QUOTATION_VALIDITY_DAYS=30
SALES_ORDER_AUTO_CONFIRM=false
```

## Service Provider Registration

All modules are registered in `bootstrap/providers.php` in priority order:

```php
return [
    Modules\Core\Providers\CoreServiceProvider::class,        // Priority 1
    Modules\Tenant\Providers\TenantServiceProvider::class,    // Priority 2
    Modules\Auth\Providers\AuthServiceProvider::class,        // Priority 3
    Modules\Audit\Providers\AuditServiceProvider::class,      // Priority 4
    Modules\Product\Providers\ProductServiceProvider::class,  // Priority 5
    Modules\Pricing\Providers\PricingServiceProvider::class,  // Priority 6
    Modules\CRM\Providers\CRMServiceProvider::class,          // Priority 7
    Modules\Sales\Providers\SalesServiceProvider::class,      // Priority 8
    Modules\Purchase\Providers\PurchaseServiceProvider::class,// Priority 9
    Modules\Inventory\Providers\InventoryServiceProvider::class,// Priority 10
    Modules\Accounting\Providers\AccountingServiceProvider::class,// Priority 11
    Modules\Notification\Providers\NotificationServiceProvider::class,// Priority 12
    Modules\Billing\Providers\BillingServiceProvider::class,  // Priority 12
    Modules\Reporting\Providers\ReportingServiceProvider::class,// Priority 13
    Modules\Document\Providers\DocumentServiceProvider::class,// Priority 13
    Modules\Workflow\Providers\WorkflowServiceProvider::class,// Priority 14
];
```

## Module Health Checks

### Health Check Results (All Passing)

```
┌──────────────┬────────────┬───────────────┐
│ Module       │ Status     │ Checks Passed │
├──────────────┼────────────┼───────────────┤
│ Core         │ ✅ Healthy │ 6/6           │
│ Tenant       │ ✅ Healthy │ 6/6           │
│ Auth         │ ✅ Healthy │ 6/6           │
│ Audit        │ ✅ Healthy │ 6/6           │
│ Product      │ ✅ Healthy │ 6/6           │
│ Pricing      │ ✅ Healthy │ 6/6           │
│ CRM          │ ✅ Healthy │ 6/6           │
│ Sales        │ ✅ Healthy │ 6/6           │
│ Purchase     │ ✅ Healthy │ 6/6           │
│ Inventory    │ ✅ Healthy │ 6/6           │
│ Accounting   │ ✅ Healthy │ 6/6           │
│ Notification │ ✅ Healthy │ 6/6           │
│ Billing      │ ✅ Healthy │ 6/6           │
│ Reporting    │ ✅ Healthy │ 6/6           │
│ Document     │ ✅ Healthy │ 6/6           │
│ Workflow     │ ✅ Healthy │ 6/6           │
└──────────────┴────────────┴───────────────┘

Total: 16 | Healthy: 16 | Issues: 0
```

### Health Check Categories

1. **Structure**: Module directory and essential folders exist
2. **Provider**: Service provider exists and is registered
3. **Migrations**: Database migrations (if any) are valid
4. **Routes**: Route files (if any) are valid
5. **Config**: Configuration files (if any) are valid
6. **Dependencies**: All module dependencies are enabled

## Best Practices

### 1. Adding a New Module

```bash
# 1. Create module structure
mkdir -p modules/NewModule/{Providers,Models,Repositories,Services,Controllers}

# 2. Create service provider
# modules/NewModule/Providers/NewModuleServiceProvider.php

# 3. Register in config/modules.php
'NewModule' => [
    'enabled' => env('MODULE_NEWMODULE_ENABLED', true),
    'priority' => 15, // Higher than all dependencies
    'dependencies' => ['Core', 'Tenant', ...],
],

# 4. Register in bootstrap/providers.php
Modules\NewModule\Providers\NewModuleServiceProvider::class,

# 5. Validate
php artisan modules:validate-dependencies
php artisan modules:health-check NewModule
```

### 2. Adding a Module Dependency

```php
// ❌ WRONG: Direct module import
use Modules\Sales\Models\Order;

// ✅ CORRECT: Event-driven
event(new PaymentReceived($payment));

// ✅ CORRECT: Service contract
app(PricingEngineInterface::class)->calculate($product);

// ✅ CORRECT: Repository pattern
app(CustomerRepository::class)->find($customerId);
```

### 3. Module Communication Patterns

```php
// Pattern 1: Events (Preferred for decoupling)
event(new OrderCreated($order));

// Pattern 2: Service Contracts
$pricing = app(PricingEngineInterface::class);
$price = $pricing->calculate($product, $customer);

// Pattern 3: Repository Pattern
$customer = app(CustomerRepository::class)->find($customerId);

// Pattern 4: Dependency Injection
public function __construct(
    private readonly CustomerRepository $customers,
    private readonly PricingEngineInterface $pricing
) {}
```

## Maintenance

### Regular Checks

```bash
# Run before deployments
php artisan modules:validate-dependencies
php artisan modules:health-check
php artisan test

# Check configuration
php artisan config:show modules

# Clear caches
php artisan config:clear
php artisan cache:clear
```

### Troubleshooting

#### Circular Dependency Detected
```bash
# Run validation
php artisan modules:validate-dependencies

# Review error output
# Fix by adjusting module priorities or dependencies
# Re-validate
```

#### Module Health Check Failed
```bash
# Check specific module
php artisan modules:health-check Sales --details

# Review failed checks
# Fix issues and re-check
```

## Architecture Benefits

### ✅ Maintainability
- Clear separation of concerns
- Easy to locate functionality
- Consistent structure across modules

### ✅ Scalability
- Modules can be deployed independently
- Can scale specific modules based on load
- Easy to add new modules

### ✅ Testability
- Modules can be tested in isolation
- Clear boundaries for unit tests
- Integration tests at module boundaries

### ✅ Reusability
- Modules can be reused across projects
- Clear interfaces for integration
- Well-documented dependencies

### ✅ Security
- Tenant isolation enforced at module level
- Authorization via policies
- Audit logging across all modules

## References

- [Clean Architecture](https://blog.cleancoder.com/atom.xml)
- [Modular Design](https://en.wikipedia.org/wiki/Modular_design)
- [Plugin Architecture](https://en.wikipedia.org/wiki/Plug-in_(computing))
- [Laravel Package Development](https://laravel.com/docs/12.x/packages)
- [Building Modular Systems in Laravel](https://sevalla.com/blog/building-modular-systems-laravel)
