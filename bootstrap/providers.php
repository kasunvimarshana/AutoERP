<?php

use App\Providers\AppServiceProvider;
use Modules\Auth\Infrastructure\Providers\AuthModuleServiceProvider;
use Modules\Account\Infrastructure\Providers\AccountServiceProvider;
use Modules\Customer\Infrastructure\Providers\CustomerServiceProvider;
use Modules\Location\Infrastructure\Providers\LocationServiceProvider;
use Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider;
use Modules\StockMovement\Infrastructure\Providers\StockMovementServiceProvider;
use Modules\Returns\Infrastructure\Providers\ReturnsServiceProvider;
use Modules\GS1\Infrastructure\Providers\GS1ServiceProvider;
use Modules\Settings\Infrastructure\Providers\SettingsServiceProvider;
use Modules\PurchaseOrder\Infrastructure\Providers\PurchaseOrderServiceProvider;
use Modules\SalesOrder\Infrastructure\Providers\SalesOrderServiceProvider;
use Modules\GoodsReceipt\Infrastructure\Providers\GoodsReceiptServiceProvider;
use Modules\Dispatch\Infrastructure\Providers\DispatchServiceProvider;
use Modules\UoM\Infrastructure\Providers\UomServiceProvider;
use Modules\Warehouse\Infrastructure\Providers\WarehouseServiceProvider;
use Modules\Supplier\Infrastructure\Providers\SupplierServiceProvider;
use Modules\HR\Infrastructure\Providers\HRServiceProvider;
use Modules\Brand\Infrastructure\Providers\BrandServiceProvider;
use Modules\Pricing\Infrastructure\Providers\PricingServiceProvider;
use Modules\Taxation\Infrastructure\Providers\TaxationServiceProvider;
use Modules\Category\Infrastructure\Providers\CategoryServiceProvider;
use Modules\Transaction\Infrastructure\Providers\TransactionServiceProvider;
use Modules\Core\Infrastructure\Providers\CoreServiceProvider;
use Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider;
use Modules\Product\Infrastructure\Providers\ProductServiceProvider;
use Modules\Tenant\Infrastructure\Providers\TenantConfigServiceProvider;
use Modules\Tenant\Infrastructure\Providers\TenantServiceProvider;
use Modules\User\Infrastructure\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    TenantServiceProvider::class,
    TenantConfigServiceProvider::class,
    UserServiceProvider::class,
    OrganizationUnitServiceProvider::class,
    AuthModuleServiceProvider::class,
    ProductServiceProvider::class,
    BrandServiceProvider::class,
    CategoryServiceProvider::class,
    AccountServiceProvider::class,
    SupplierServiceProvider::class,
    CustomerServiceProvider::class,
    LocationServiceProvider::class,
    WarehouseServiceProvider::class,
    UomServiceProvider::class,
    InventoryServiceProvider::class,
    StockMovementServiceProvider::class,
    ReturnsServiceProvider::class,
    GS1ServiceProvider::class,
    SettingsServiceProvider::class,
    PurchaseOrderServiceProvider::class,
    GoodsReceiptServiceProvider::class,
    SalesOrderServiceProvider::class,
    DispatchServiceProvider::class,
    HRServiceProvider::class,
    PricingServiceProvider::class,
    TaxationServiceProvider::class,
    TransactionServiceProvider::class,
];
