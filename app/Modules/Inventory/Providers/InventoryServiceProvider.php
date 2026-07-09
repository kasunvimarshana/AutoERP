<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Inventory\Constants\InventoryPermission;
use Modules\Inventory\Services\Item\InventoryBaseUomConversionAdapter;
use Modules\Item\Contracts\InventoryBaseUomConversionInterface;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/inventory.php', 'inventory');
        $this->app->singleton(
            InventoryBaseUomConversionInterface::class,
            InventoryBaseUomConversionAdapter::class,
        );
    }

    public function boot(): void
    {
        $this->app->make(ConfigurationDefinitionRegistryInterface::class)
            ->register('Inventory', require __DIR__.'/../Config/configuration-definitions.php');
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('inventory', InventoryPermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
