<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\Audit\Infrastructure\Providers\AuditServiceProvider;
use Modules\Auth\Infrastructure\Providers\AuthModuleServiceProvider;
use Modules\Configuration\Infrastructure\Providers\ConfigurationServiceProvider;
use Modules\Core\Infrastructure\Providers\CoreServiceProvider;
use Modules\Employee\Infrastructure\Providers\EmployeeServiceProvider;
use Modules\Finance\Infrastructure\Providers\FinanceServiceProvider;
use Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider;
use Modules\Product\Infrastructure\Providers\ProductServiceProvider;
use Modules\Shared\Infrastructure\Providers\SharedServiceProvider;
use Modules\Tenant\Infrastructure\Providers\TenantConfigServiceProvider;
use Modules\Tenant\Infrastructure\Providers\TenantServiceProvider;
use Modules\User\Infrastructure\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    ConfigurationServiceProvider::class,
    SharedServiceProvider::class,
    AuditServiceProvider::class,
    AuthModuleServiceProvider::class,
    TenantServiceProvider::class,
    TenantConfigServiceProvider::class,
    UserServiceProvider::class,
    OrganizationUnitServiceProvider::class,
    ProductServiceProvider::class,
    EmployeeServiceProvider::class,
    FinanceServiceProvider::class,
];
