<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Core module service provider.
 *
 * Bootstraps the core infrastructure: registers base bindings,
 * loads configurations, and publishes shared assets.
 * No business logic resides here.
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register core bindings into the service container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/core.php',
            'core'
        );
    }

    /**
     * Bootstrap core services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/core.php' => config_path('core.php'),
        ], 'core-config');
    }
}
