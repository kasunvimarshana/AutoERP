<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\Analytics\Infrastructure\Providers\AnalyticsServiceProvider;
use Modules\Audit\Infrastructure\Providers\AuditServiceProvider;
use Modules\Auth\Infrastructure\Providers\AuthModuleServiceProvider;
use Modules\Configuration\Infrastructure\Providers\ConfigurationServiceProvider;
use Modules\Core\Infrastructure\Providers\CoreServiceProvider;
use Modules\Customer\Infrastructure\Providers\CustomerServiceProvider;
use Modules\Employee\Infrastructure\Providers\EmployeeServiceProvider;
use Modules\Finance\Infrastructure\Providers\FinanceServiceProvider;
use Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider;
use Modules\Invoicing\Infrastructure\Providers\InvoicingServiceProvider;
use Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider;
use Modules\Payments\Infrastructure\Providers\PaymentsServiceProvider;
use Modules\Pricing\Infrastructure\Providers\PricingServiceProvider;
use Modules\Product\Infrastructure\Providers\ProductServiceProvider;
use Modules\Purchase\Infrastructure\Providers\PurchaseServiceProvider;
use Modules\HR\Infrastructure\Providers\HRServiceProvider;
use Modules\Driver\Infrastructure\Providers\DriverServiceProvider;
use Modules\Fleet\Infrastructure\Providers\FleetServiceProvider;
use Modules\Rental\Infrastructure\Providers\RentalServiceProvider;
use Modules\Reservation\Infrastructure\Providers\ReservationServiceProvider;
use Modules\Receipts\Infrastructure\Providers\ReceiptsServiceProvider;
use Modules\ReturnRefund\Infrastructure\Providers\ReturnRefundServiceProvider;
use Modules\FuelTracking\Infrastructure\Providers\FuelTrackingServiceProvider;
use Modules\Notifications\Infrastructure\Providers\NotificationsServiceProvider;
use Modules\ServiceCenter\Infrastructure\Providers\ServiceCenterServiceProvider;
use Modules\Sales\Infrastructure\Providers\SalesServiceProvider;
use Modules\Shared\Infrastructure\Providers\SharedServiceProvider;
use Modules\Supplier\Infrastructure\Providers\SupplierServiceProvider;
use Modules\Tax\Infrastructure\Providers\TaxServiceProvider;
use Modules\Tenant\Infrastructure\Providers\TenantConfigServiceProvider;
use Modules\Tenant\Infrastructure\Providers\TenantServiceProvider;
use Modules\User\Infrastructure\Providers\UserServiceProvider;
use Modules\Warehouse\Infrastructure\Providers\WarehouseServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    ConfigurationServiceProvider::class,
    SharedServiceProvider::class,
    AnalyticsServiceProvider::class,
    AuditServiceProvider::class,
    AuthModuleServiceProvider::class,
    TenantServiceProvider::class,
    TenantConfigServiceProvider::class,
    UserServiceProvider::class,
    OrganizationUnitServiceProvider::class,
    ProductServiceProvider::class,
    PricingServiceProvider::class,
    CustomerServiceProvider::class,
    EmployeeServiceProvider::class,
    SupplierServiceProvider::class,
    TaxServiceProvider::class,
    FinanceServiceProvider::class,
    InvoicingServiceProvider::class,
    PaymentsServiceProvider::class,
    InventoryServiceProvider::class,
    WarehouseServiceProvider::class,
    PurchaseServiceProvider::class,
    SalesServiceProvider::class,
    HRServiceProvider::class,
    FleetServiceProvider::class,
    DriverServiceProvider::class,
    RentalServiceProvider::class,
    ReservationServiceProvider::class,
    ReceiptsServiceProvider::class,
    ReturnRefundServiceProvider::class,
    ServiceCenterServiceProvider::class,
    NotificationsServiceProvider::class,
    FuelTrackingServiceProvider::class,
];
