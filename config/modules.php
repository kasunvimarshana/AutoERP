<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Registry Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the central configuration for the module registry
    | system. All modules must be registered here to be loaded by the
    | application. Modules can be enabled/disabled at runtime via this
    | configuration.
    |
    */

    'enabled' => env('MODULES_ENABLED', true),

    'auto_discover' => env('MODULES_AUTO_DISCOVER', true),

    'modules_path' => base_path('modules'),

    'namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Registered Modules
    |--------------------------------------------------------------------------
    |
    | List of all available modules in the system. Each module can be
    | enabled or disabled individually. The order matters for dependency
    | resolution - modules are loaded in the order specified here.
    |
    */

    'registered' => [
        'Core' => [
            'enabled' => env('MODULE_CORE_ENABLED', true),
            'priority' => 1, // Highest priority - loaded first
            'dependencies' => [],
            'provides' => [
                'ModuleInterface',
                'MathHelper',
                'TransactionHelper',
            ],
        ],
        'Tenant' => [
            'enabled' => env('MODULE_TENANT_ENABLED', true),
            'priority' => 2,
            'dependencies' => ['Core'],
            'provides' => [
                'TenantContext',
                'TenantScoped',
            ],
        ],
        'Auth' => [
            'enabled' => env('MODULE_AUTH_ENABLED', true),
            'priority' => 3,
            'dependencies' => ['Core', 'Tenant'],
            'provides' => [
                'JwtTokenService',
                'TokenServiceInterface',
            ],
        ],
        'Audit' => [
            'enabled' => env('MODULE_AUDIT_ENABLED', true),
            'priority' => 4,
            'dependencies' => ['Core', 'Tenant', 'Auth'],
            'provides' => [
                'AuditService',
                'Auditable',
            ],
        ],
        'Product' => [
            'enabled' => env('MODULE_PRODUCT_ENABLED', true),
            'priority' => 5,
            'dependencies' => ['Core', 'Tenant', 'Audit'],
            'provides' => [
                'ProductService',
            ],
        ],
        'Pricing' => [
            'enabled' => env('MODULE_PRICING_ENABLED', true),
            'priority' => 6,
            'dependencies' => ['Core', 'Tenant', 'Product'],
            'provides' => [
                'PricingService',
                'PricingEngineInterface',
            ],
        ],
        'CRM' => [
            'enabled' => env('MODULE_CRM_ENABLED', true),
            'priority' => 7,
            'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit'],
            'provides' => [
                'CustomerRepository',
                'LeadRepository',
                'OpportunityRepository',
                'LeadConversionService',
                'OpportunityService',
            ],
        ],
        'Sales' => [
            'enabled' => env('MODULE_SALES_ENABLED', true),
            'priority' => 8,
            'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit', 'Product', 'Pricing', 'CRM'],
            'provides' => [
                'QuotationRepository',
                'OrderRepository',
                'InvoiceRepository',
                'QuotationService',
                'OrderService',
                'InvoiceService',
            ],
        ],
        'Purchase' => [
            'enabled' => env('MODULE_PURCHASE_ENABLED', true),
            'priority' => 9,
            'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit', 'Product', 'Pricing'],
            'provides' => [
                'VendorRepository',
                'PurchaseOrderRepository',
                'GoodsReceiptRepository',
                'BillRepository',
                'VendorService',
                'PurchaseOrderService',
                'GoodsReceiptService',
                'BillService',
            ],
        ],
        'Inventory' => [
            'enabled' => env('MODULE_INVENTORY_ENABLED', true),
            'priority' => 10,
            'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit', 'Product', 'Sales', 'Purchase'],
            'provides' => [
                'WarehouseRepository',
                'StockItemRepository',
                'StockMovementRepository',
                'StockCountRepository',
                'WarehouseService',
                'StockMovementService',
                'InventoryValuationService',
                'StockCountService',
                'ReorderService',
                'SerialNumberService',
            ],
        ],
        'Accounting' => [
            'enabled' => env('MODULE_ACCOUNTING_ENABLED', true),
            'priority' => 11,
            'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit', 'Sales', 'Purchase', 'Inventory'],
            'provides' => [
                'AccountRepository',
                'JournalEntryRepository',
                'FiscalPeriodRepository',
                'AccountingService',
                'ChartOfAccountsService',
                'GeneralLedgerService',
                'TrialBalanceService',
                'FinancialStatementService',
            ],
        ],
        'Notification' => [
            'enabled' => env('MODULE_NOTIFICATION_ENABLED', true),
            'priority' => 12,
            'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit'],
            'provides' => [
                'NotificationRepository',
                'TemplateRepository',
                'ChannelRepository',
                'NotificationLogRepository',
                'NotificationService',
                'TemplateService',
                'ChannelService',
                'NotificationDeliveryService',
            ],
        ],
        'Billing' => [
            'enabled' => env('MODULE_BILLING_ENABLED', true),
            'priority' => 12,
            'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit'],
            'provides' => [
                'PlanRepository',
                'SubscriptionRepository',
                'SubscriptionPaymentRepository',
                'SubscriptionService',
                'PaymentService',
                'BillingCalculationService',
                'UsageTrackingService',
            ],
        ],
        'Reporting' => [
            'enabled' => env('MODULE_REPORTING_ENABLED', true),
            'priority' => 13,
            'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit'],
            'provides' => [
                'ReportRepository',
                'DashboardRepository',
                'WidgetRepository',
                'ReportBuilder',
                'ExportService',
                'AnalyticsService',
            ],
        ],
        'Document' => [
            'enabled' => env('MODULE_DOCUMENT_ENABLED', true),
            'priority' => 13,
            'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit'],
            'provides' => [
                'DocumentRepository',
                'FolderRepository',
                'VersionRepository',
                'DocumentStorageService',
                'VersionControlService',
                'SharingService',
            ],
        ],
        'Workflow' => [
            'enabled' => env('MODULE_WORKFLOW_ENABLED', true),
            'priority' => 14,
            'dependencies' => ['Core', 'Tenant', 'Auth', 'Audit'],
            'provides' => [
                'WorkflowRepository',
                'WorkflowInstanceRepository',
                'ApprovalRepository',
                'WorkflowEngine',
                'WorkflowExecutor',
                'ApprovalService',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Caching
    |--------------------------------------------------------------------------
    |
    | Module information can be cached to improve performance. When enabled,
    | module configuration and providers are cached and only reloaded when
    | the cache is cleared.
    |
    */

    'cache' => [
        'enabled' => env('MODULE_CACHE_ENABLED', ! env('APP_DEBUG', false)),
        'key' => 'modules.cache',
        'ttl' => env('MODULE_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable event firing for module lifecycle events
    | (loading, loaded, enabling, enabled, disabling, disabled).
    |
    */

    'events' => [
        'enabled' => env('MODULE_EVENTS_ENABLED', true),
    ],
];
