<?php

declare(strict_types=1);

namespace Modules\Selling\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Selling\Services\SellingAuthorizationService;

final class SellingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('selling', SellingAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
