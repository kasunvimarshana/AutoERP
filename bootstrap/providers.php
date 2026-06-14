<?php

use App\Providers\AppServiceProvider;
use Modules\Audit\Providers\AuditServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Configuration\Providers\ConfigurationServiceProvider;
use Modules\Core\Providers\CoreServiceProvider;
use Modules\Customer\Providers\CustomerServiceProvider;
use Modules\Extension\Providers\ExtensionServiceProvider;
use Modules\Finance\Providers\FinanceServiceProvider;
use Modules\Hr\Providers\HrServiceProvider;
use Modules\Inventory\Providers\InventoryServiceProvider;
use Modules\Invoice\Providers\InvoiceServiceProvider;
use Modules\Item\Providers\ItemServiceProvider;
use Modules\OrganizationUnit\Providers\OrganizationUnitServiceProvider;
use Modules\Payment\Providers\PaymentServiceProvider;
use Modules\Purchase\Providers\PurchaseServiceProvider;
use Modules\Reporting\Providers\ReportingServiceProvider;
use Modules\Sales\Providers\SalesServiceProvider;
use Modules\Sequence\Providers\SequenceServiceProvider;
use Modules\Supplier\Providers\SupplierServiceProvider;
use Modules\Tax\Providers\TaxServiceProvider;
use Modules\Tenant\Providers\TenantServiceProvider;
use Modules\UOM\Providers\UomServiceProvider;
use Modules\User\Providers\UserServiceProvider;
use Modules\Vehicle\Providers\VehicleServiceProvider;
use Modules\VehicleRental\Providers\VehicleRentalServiceProvider;
use Modules\VehicleService\Providers\VehicleServiceServiceProvider;
use Modules\Warehouse\Providers\WarehouseServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    ConfigurationServiceProvider::class,
    AuthServiceProvider::class,
    UserServiceProvider::class,
    TenantServiceProvider::class,
    SequenceServiceProvider::class,
    OrganizationUnitServiceProvider::class,
    InvoiceServiceProvider::class,
    PaymentServiceProvider::class,
    FinanceServiceProvider::class,
    ReportingServiceProvider::class,
    HrServiceProvider::class,
    ItemServiceProvider::class,
    InventoryServiceProvider::class,
    PurchaseServiceProvider::class,
    SalesServiceProvider::class,
    SupplierServiceProvider::class,
    TaxServiceProvider::class,
    CustomerServiceProvider::class,
    VehicleServiceProvider::class,
    VehicleRentalServiceProvider::class,
    VehicleServiceServiceProvider::class,
    UomServiceProvider::class,
    AuditServiceProvider::class,
    ExtensionServiceProvider::class,
    WarehouseServiceProvider::class,
];
