<?php

declare(strict_types=1);

namespace Modules\Supplier\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('supplier', SupplierAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
