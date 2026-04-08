<?php

return [
    Modules\Core\Infrastructure\Providers\CoreServiceProvider::class,
    Modules\Tenant\Infrastructure\Providers\TenantServiceProvider::class,
    Modules\Auth\Infrastructure\Providers\AuthServiceProvider::class,
    Modules\Configuration\Infrastructure\Providers\ConfigurationServiceProvider::class,
    Modules\Currency\Infrastructure\Providers\CurrencyServiceProvider::class,
    Modules\Tax\Infrastructure\Providers\TaxServiceProvider::class,
    Modules\Audit\Infrastructure\Providers\AuditServiceProvider::class,
    Modules\Pricing\Infrastructure\Providers\PricingServiceProvider::class,
    Modules\Warehouse\Infrastructure\Providers\WarehouseServiceProvider::class,
    Modules\Supplier\Infrastructure\Providers\SupplierServiceProvider::class,
    Modules\Customer\Infrastructure\Providers\CustomerServiceProvider::class,
    Modules\Product\Infrastructure\Providers\ProductServiceProvider::class,
    Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider::class,
    Modules\Order\Infrastructure\Providers\OrderServiceProvider::class,
    Modules\Transaction\Infrastructure\Providers\TransactionServiceProvider::class,
    Modules\Returns\Infrastructure\Providers\ReturnsServiceProvider::class,
];
