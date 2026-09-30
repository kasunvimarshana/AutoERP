<?php

declare(strict_types=1);

namespace Modules\Item\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\Tax\ItemTaxContextProvider;
use Modules\Tax\Contracts\TaxItemContextProviderInterface;

final class ItemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TaxItemContextProviderInterface::class, ItemTaxContextProvider::class);
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('item', ItemAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom([__DIR__.'/../Database/Migrations', __DIR__.'/../Database/UpgradeMigrations']);
    }
}
