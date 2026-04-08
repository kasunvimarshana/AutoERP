<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Providers;

/**
 * Registry of all module service providers.
 * Register each entry in bootstrap/providers.php or config/app.php providers array.
 */
final class ModuleRegistry
{
    public const PROVIDERS = [
        \Modules\Core\Infrastructure\Providers\CoreServiceProvider::class,
        \Modules\Auth\Infrastructure\Providers\AuthServiceProvider::class,
        \Modules\Tenant\Infrastructure\Providers\TenantServiceProvider::class,
        \Modules\Product\Infrastructure\Providers\ProductServiceProvider::class,
        \Modules\Finance\Infrastructure\Providers\FinanceServiceProvider::class,
        \Modules\Warehouse\Infrastructure\Providers\WarehouseServiceProvider::class,
        \Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider::class,
        \Modules\Supplier\Infrastructure\Providers\SupplierServiceProvider::class,
        \Modules\Customer\Infrastructure\Providers\CustomerServiceProvider::class,
        \Modules\Order\Infrastructure\Providers\OrderServiceProvider::class,
        \Modules\Returns\Infrastructure\Providers\ReturnsServiceProvider::class,
    ];
}
