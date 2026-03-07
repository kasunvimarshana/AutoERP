<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadModuleRoutes();
    }

    private function loadModuleRoutes(): void
    {
        $modules = [
            'Auth',
            'User',
            'Product',
            'Inventory',
            'Order',
        ];

        foreach ($modules as $module) {
            $routesFile = app_path("Modules/{$module}/Routes/api.php");

            if (file_exists($routesFile)) {
                Route::middleware('api')
                    ->prefix('api/v1')
                    ->group($routesFile);
            }
        }
    }
}
