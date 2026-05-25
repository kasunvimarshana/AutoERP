<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    Modules\Core\Infrastructure\Providers\CoreServiceProvider::class,
    Modules\Configuration\Infrastructure\Providers\ConfigurationServiceProvider::class,
    Modules\User\Infrastructure\Providers\UserServiceProvider::class,
    Modules\Tenant\Infrastructure\Providers\TenantServiceProvider::class,
    Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider::class,
];
