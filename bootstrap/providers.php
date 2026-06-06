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
    Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider::class,
    Modules\UOM\Infrastructure\Providers\UomServiceProvider::class,
    Modules\Audit\Infrastructure\Providers\AuditServiceProvider::class,
    Modules\Extension\Infrastructure\Providers\ExtensionServiceProvider::class,
    Modules\Warehouse\Infrastructure\Providers\WarehouseServiceProvider::class,
];
