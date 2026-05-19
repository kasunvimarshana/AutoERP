<?php

use App\Providers\AppServiceProvider;
use Modules\Configuration\Infrastructure\Providers\ConfigurationServiceProvider;
use Modules\Tenant\Infrastructure\Providers\TenantServiceProvider;
use Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider;
use Modules\Extension\Infrastructure\Providers\ExtensionServiceProvider;
use Modules\User\Infrastructure\Providers\UserServiceProvider;
use Modules\Audit\Infrastructure\Providers\AuditServiceProvider;
use Modules\Auth\Infrastructure\Providers\AuthServiceProvider;
use Modules\Sequence\Infrastructure\Providers\SequenceServiceProvider;
use Modules\Finance\Infrastructure\Providers\FinanceServiceProvider;
use Modules\Item\Infrastructure\Providers\ItemServiceProvider;
use Modules\UOM\Infrastructure\Providers\UOMServiceProvider;
use Modules\SystemUser\Infrastructure\Providers\SystemUserServiceProvider;
use Modules\HR\Infrastructure\Providers\HRServiceProvider;
use Modules\Customer\Infrastructure\Providers\CustomerServiceProvider;
use Modules\Supplier\Infrastructure\Providers\SupplierServiceProvider;
use Modules\Pricing\Infrastructure\Providers\PricingServiceProvider;
use Modules\Vehicle\Infrastructure\Providers\VehicleServiceProvider;
use Modules\Warehouse\Infrastructure\Providers\WarehouseServiceProvider;
use Modules\Purchase\Infrastructure\Providers\PurchaseServiceProvider;
use Modules\Sale\Infrastructure\Providers\SaleServiceProvider;
use Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider;
use Modules\VehicleService\Infrastructure\Providers\VehicleServiceServiceProvider;
use Modules\VehicleRental\Infrastructure\Providers\VehicleRentalServiceProvider;
use Modules\Voucher\Infrastructure\Providers\VoucherServiceProvider;
use Modules\Invoice\Infrastructure\Providers\InvoiceServiceProvider;
use Modules\Payment\Infrastructure\Providers\PaymentServiceProvider;
// use Modules\Reporting\Infrastructure\Providers\ReportingServiceProvider;

return [
    AppServiceProvider::class,

    ConfigurationServiceProvider::class,
    TenantServiceProvider::class,
    OrganizationUnitServiceProvider::class,
    ExtensionServiceProvider::class,
    UserServiceProvider::class,
    AuditServiceProvider::class,
    AuthServiceProvider::class,
    SequenceServiceProvider::class,
    FinanceServiceProvider::class,
    ItemServiceProvider::class,
    UOMServiceProvider::class,
    SystemUserServiceProvider::class,
    HRServiceProvider::class,
    CustomerServiceProvider::class,
    SupplierServiceProvider::class,
    PricingServiceProvider::class,
    VehicleServiceProvider::class,
    WarehouseServiceProvider::class,
    PurchaseServiceProvider::class,
    SaleServiceProvider::class,
    InventoryServiceProvider::class,
    VehicleServiceServiceProvider::class,
    VehicleRentalServiceProvider::class,
    VoucherServiceProvider::class,
    InvoiceServiceProvider::class,
    PaymentServiceProvider::class,
    // ReportingServiceProvider::class
];
