<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    Modules\Audit\Infrastructure\Providers\AuditServiceProvider::class,
    Modules\Configuration\Infrastructure\Providers\ConfigurationServiceProvider::class,
    Modules\Customer\Infrastructure\Providers\CustomerServiceProvider::class,
    Modules\Extension\Infrastructure\Providers\ExtensionServiceProvider::class,
    Modules\Finance\Infrastructure\Providers\FinanceServiceProvider::class,
    Modules\HR\Infrastructure\Providers\HRServiceProvider::class,
    Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider::class,
    Modules\Invoice\Infrastructure\Providers\InvoiceServiceProvider::class,
    Modules\Item\Infrastructure\Providers\ItemServiceProvider::class,
    Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider::class,
    Modules\Payment\Infrastructure\Providers\PaymentServiceProvider::class,
    Modules\Pricing\Infrastructure\Providers\PricingServiceProvider::class,
    Modules\Purchase\Infrastructure\Providers\PurchaseServiceProvider::class,
    Modules\Sales\Infrastructure\Providers\SaleServiceProvider::class,
    Modules\Sequence\Infrastructure\Providers\SequenceServiceProvider::class,
    Modules\Supplier\Infrastructure\Providers\SupplierServiceProvider::class,
    Modules\SystemUser\Infrastructure\Providers\SystemUserServiceProvider::class,
    Modules\Tenant\Infrastructure\Providers\TenantServiceProvider::class,
    Modules\UOM\Infrastructure\Providers\UOMServiceProvider::class,
    Modules\User\Infrastructure\Providers\UserServiceProvider::class,
    Modules\Vehicle\Infrastructure\Providers\VehicleServiceProvider::class,
    Modules\VehicleRental\Infrastructure\Providers\VehicleRentalServiceProvider::class,
    Modules\VehicleService\Infrastructure\Providers\VehicleServiceServiceProvider::class,
    Modules\Voucher\Infrastructure\Providers\VoucherServiceProvider::class,
    Modules\Warehouse\Infrastructure\Providers\WarehouseServiceProvider::class,
];
