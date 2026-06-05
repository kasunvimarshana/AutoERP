<?php

use App\Providers\AppServiceProvider;
use Modules\Audit\Infrastructure\Providers\AuditServiceProvider;
use Modules\Auth\Infrastructure\Providers\AuthServiceProvider;
use Modules\Configuration\Infrastructure\Providers\ConfigurationServiceProvider;
use Modules\Core\Infrastructure\Providers\CoreServiceProvider;
use Modules\Customer\Infrastructure\Providers\CustomerServiceProvider;
use Modules\Finance\Infrastructure\Providers\FinanceServiceProvider;
use Modules\HR\Infrastructure\Providers\HrServiceProvider;
use Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider;
use Modules\Invoice\Infrastructure\Providers\InvoiceServiceProvider;
use Modules\Item\Infrastructure\Providers\ItemServiceProvider;
use Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider;
use Modules\Payment\Infrastructure\Providers\PaymentServiceProvider;
use Modules\Purchase\Infrastructure\Providers\PurchaseServiceProvider;
use Modules\Sales\Infrastructure\Providers\SalesServiceProvider;
use Modules\Sequence\Infrastructure\Providers\SequenceServiceProvider;
use Modules\Supplier\Infrastructure\Providers\SupplierServiceProvider;
use Modules\Tenant\Infrastructure\Providers\TenantServiceProvider;
use Modules\UOM\Infrastructure\Providers\UomServiceProvider;
use Modules\User\Infrastructure\Providers\UserServiceProvider;
use Modules\Vehicle\Infrastructure\Providers\VehicleServiceProvider;
use Modules\VehicleService\Infrastructure\Providers\VehicleServiceServiceProvider;
use Modules\Warehouse\Infrastructure\Providers\WarehouseServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    ConfigurationServiceProvider::class,
    AuthServiceProvider::class,
    UserServiceProvider::class,
    TenantServiceProvider::class,
    SequenceServiceProvider::class,
    OrganizationUnitServiceProvider::class,
    FinanceServiceProvider::class,
    VehicleServiceProvider::class,
    HrServiceProvider::class,
    ItemServiceProvider::class,
    InventoryServiceProvider::class,
    UomServiceProvider::class,
    AuditServiceProvider::class,
    CustomerServiceProvider::class,
    InvoiceServiceProvider::class,
    PaymentServiceProvider::class,
    PurchaseServiceProvider::class,
    SalesServiceProvider::class,
    SupplierServiceProvider::class,
    VehicleServiceServiceProvider::class,
    WarehouseServiceProvider::class,
];
