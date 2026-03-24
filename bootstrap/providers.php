<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    Modules\Core\Infrastructure\Providers\CoreServiceProvider::class,
    Modules\Tenant\Infrastructure\Providers\TenantServiceProvider::class,
    Modules\Tenant\Infrastructure\Providers\TenantConfigServiceProvider::class,
    Modules\User\Infrastructure\Providers\UserServiceProvider::class,
    Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider::class,
];
