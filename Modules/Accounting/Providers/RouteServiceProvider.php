<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Route Service Provider
 *
 * Registers module routes.
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Accounting\Http\Controllers';

    /**
     * Called before routes are registered.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the module.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
    }

    /**
     * Define the "api" routes for the module.
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api/accounting')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Accounting', 'Routes/api.php'));
    }
}
