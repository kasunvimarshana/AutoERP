<?php

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Factories\AllocationRuleFactory;
use Modules\Inventory\Application\Factories\AllocationStrategyFactory;
use Modules\Inventory\Application\Factories\ValuationStrategyFactory;
use Modules\Inventory\Application\Services\InventoryAllocationService;
use Modules\Inventory\Application\Services\InventoryValuationService;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../Infrastructure/Config/inventory.php', 'inventory');

        $this->app->singleton(ValuationStrategyFactory::class, function ($app): ValuationStrategyFactory {
            return new ValuationStrategyFactory(
                container: $app,
                strategies: (array) config('inventory.valuation.methods', []),
            );
        });

        $this->app->singleton(AllocationStrategyFactory::class, function ($app): AllocationStrategyFactory {
            return new AllocationStrategyFactory(
                container: $app,
                strategies: (array) config('inventory.allocation.methods', []),
            );
        });

        $this->app->singleton(AllocationRuleFactory::class, function ($app): AllocationRuleFactory {
            return new AllocationRuleFactory(
                container: $app,
                rules: (array) config('inventory.allocation.rules', []),
            );
        });

        $this->app->singleton(InventoryValuationService::class, function ($app): InventoryValuationService {
            return new InventoryValuationService(
                strategyFactory: $app->make(ValuationStrategyFactory::class),
            );
        });

        $this->app->singleton(InventoryAllocationService::class, function ($app): InventoryAllocationService {
            return new InventoryAllocationService(
                strategyFactory: $app->make(AllocationStrategyFactory::class),
                ruleFactory: $app->make(AllocationRuleFactory::class),
                defaultRuleKeys: (array) config('inventory.allocation.default_rules', []),
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');

        $this->publishes([
            __DIR__ . '/../../Infrastructure/Config/inventory.php' => config_path('inventory.php'),
        ], 'inventory-config');
    }
}
