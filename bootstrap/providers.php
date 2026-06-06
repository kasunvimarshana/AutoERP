<?php

use App\Providers\AppServiceProvider;
use Modules\Audit\Providers\AuditServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Configuration\Providers\ConfigurationServiceProvider;
use Modules\Core\Providers\CoreServiceProvider;
use Modules\Extension\Providers\ExtensionServiceProvider;
use Modules\Invoice\Providers\InvoiceServiceProvider;
use Modules\OrganizationUnit\Providers\OrganizationUnitServiceProvider;
use Modules\Payment\Providers\PaymentServiceProvider;
use Modules\Sequence\Providers\SequenceServiceProvider;
use Modules\Tenant\Providers\TenantServiceProvider;
use Modules\UOM\Providers\UomServiceProvider;
use Modules\User\Providers\UserServiceProvider;
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
    UomServiceProvider::class,
    AuditServiceProvider::class,
    ExtensionServiceProvider::class,
    WarehouseServiceProvider::class,
];
