<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Core\Services\ModuleRegistry;
use Modules\Core\Services\TotalCalculationService;

/**
 * CoreServiceProvider
 *
 * Bootstraps the core module system
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register the module registry as a singleton
        $this->app->singleton(ModuleRegistry::class, function ($app) {
            return new ModuleRegistry;
        });

        // Register the code generator service as a singleton
        $this->app->singleton(CodeGeneratorService::class, function ($app) {
            return new CodeGeneratorService;
        });

        // Register the total calculation service as a singleton
        $this->app->singleton(TotalCalculationService::class, function ($app) {
            return new TotalCalculationService;
        });

        // Merge core configuration
        $this->mergeConfigFrom(
            __DIR__.'/../Config/core.php',
            'core'
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish core configuration
        $this->publishes([
            __DIR__.'/../Config/core.php' => config_path('core.php'),
        ], 'core-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
