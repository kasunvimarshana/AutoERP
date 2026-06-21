<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/inventory.php', 'inventory');
        $this->registerConfigurationDefinitions();
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    private function registerConfigurationDefinitions(): void
    {
        /** @var array<string, array<string, mixed>> $existing */
        $existing = config('configuration.definitions', []);
        /** @var array<string, array<string, mixed>> $owned */
        $owned = require __DIR__.'/../Config/configuration-definitions.php';

        foreach ($owned as $key => $definition) {
            if (! is_string($key) || $key === '' || array_key_exists($key, $existing)) {
                throw new RuntimeException("Configuration definition key [{$key}] is missing or already registered.");
            }
            $existing[$key] = $definition;
        }

        config(['configuration.definitions' => $existing]);
    }
}
