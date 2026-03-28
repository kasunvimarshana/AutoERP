<?php

use App\Providers\AppServiceProvider;
use Modules\Auth\Infrastructure\Providers\AuthModuleServiceProvider;
use Modules\Account\Infrastructure\Providers\AccountServiceProvider;
use Modules\Customer\Infrastructure\Providers\CustomerServiceProvider;
use Modules\Supplier\Infrastructure\Providers\SupplierServiceProvider;
use Modules\Brand\Infrastructure\Providers\BrandServiceProvider;
use Modules\Category\Infrastructure\Providers\CategoryServiceProvider;
use Modules\Core\Infrastructure\Providers\CoreServiceProvider;
use Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider;
use Modules\Product\Infrastructure\Providers\ProductServiceProvider;
use Modules\Tenant\Infrastructure\Providers\TenantConfigServiceProvider;
use Modules\Tenant\Infrastructure\Providers\TenantServiceProvider;
use Modules\User\Infrastructure\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    TenantServiceProvider::class,
    TenantConfigServiceProvider::class,
    UserServiceProvider::class,
    OrganizationUnitServiceProvider::class,
    AuthModuleServiceProvider::class,
    ProductServiceProvider::class,
    BrandServiceProvider::class,
    CategoryServiceProvider::class,
    AccountServiceProvider::class,
    SupplierServiceProvider::class,
    CustomerServiceProvider::class,
];
