<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    Modules\Core\Infrastructure\Providers\CoreServiceProvider::class,
    Modules\Configuration\Infrastructure\Providers\ConfigurationServiceProvider::class,
    Modules\Auth\Infrastructure\Providers\AuthServiceProvider::class,
    Modules\User\Infrastructure\Providers\UserServiceProvider::class,
    Modules\Tenant\Infrastructure\Providers\TenantServiceProvider::class,
    Modules\Sequence\Infrastructure\Providers\SequenceServiceProvider::class,
    Modules\SystemUser\Infrastructure\Providers\SystemUserServiceProvider::class,
    Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider::class,
    Modules\Finance\Infrastructure\Providers\FinanceServiceProvider::class,
    Modules\Vehicle\Infrastructure\Providers\VehicleServiceProvider::class,
    Modules\HR\Infrastructure\Providers\HrServiceProvider::class,
    Modules\Item\Infrastructure\Providers\ItemServiceProvider::class,
    Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider::class,
    Modules\UOM\Infrastructure\Providers\UomServiceProvider::class,
    Modules\Audit\Infrastructure\Providers\AuditServiceProvider::class,
];
