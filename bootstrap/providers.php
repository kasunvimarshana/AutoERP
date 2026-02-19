<?php

return [
    App\Providers\AppServiceProvider::class,

    // Core Module Providers
    Modules\Core\Providers\CoreServiceProvider::class,
    Modules\Tenant\Providers\TenantServiceProvider::class,
    Modules\Auth\Providers\AuthServiceProvider::class,
    Modules\Audit\Providers\AuditServiceProvider::class,
    Modules\Audit\Providers\AuditEventServiceProvider::class,
    Modules\Product\Providers\ProductServiceProvider::class,
    Modules\Pricing\Providers\PricingServiceProvider::class,
    Modules\CRM\Providers\CRMServiceProvider::class,
    Modules\Sales\Providers\SalesServiceProvider::class,
    Modules\Purchase\Providers\PurchaseServiceProvider::class,
    Modules\Inventory\Providers\InventoryServiceProvider::class,
    Modules\Accounting\Providers\AccountingServiceProvider::class,
    Modules\Billing\Providers\BillingServiceProvider::class,
    Modules\Notification\Providers\NotificationServiceProvider::class,
    Modules\Reporting\Providers\ReportingServiceProvider::class,
    Modules\Document\Providers\DocumentServiceProvider::class,
    Modules\Workflow\Providers\WorkflowServiceProvider::class,
];
