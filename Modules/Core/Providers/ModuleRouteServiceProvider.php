<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base Module Route Service Provider
 *
 * Provides route registration for modules using native Laravel routing.
 * Compatible with Laravel 11's new routing system.
 */
abstract class ModuleRouteServiceProvider extends ServiceProvider
{
    /**
     * The module name.
     */
    protected string $moduleName;

    /**
     * The module namespace for controllers.
     */
    protected string $moduleNamespace;

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
    }

    /**
     * Register routes for the module.
     */
    protected function registerRoutes(): void
    {
        if (file_exists(module_path($this->moduleName, 'Routes/api.php'))) {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->moduleNamespace)
                ->group(module_path($this->moduleName, 'Routes/api.php'));
        }

        if (file_exists(module_path($this->moduleName, 'Routes/web.php'))) {
            Route::middleware('web')
                ->namespace($this->moduleNamespace)
                ->group(module_path($this->moduleName, 'Routes/web.php'));
        }
    }
}
