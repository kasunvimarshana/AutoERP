<?php

namespace App\Providers;

use App\Contracts\ModuleContract;
use App\Services\ModuleRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $modulesPath = base_path('modules');

        if (! File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);
        $registry = $this->app->make(ModuleRegistry::class);

        foreach ($modules as $module) {
            $moduleName = basename($module);

            // Register module service provider if exists
            $providerClass = "Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            if (class_exists($providerClass)) {
                $provider = $this->app->register($providerClass);

                // Register with module registry if implements ModuleContract
                if ($provider instanceof ModuleContract) {
                    $registry->register($provider);
                }
            }
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $modulesPath = base_path('modules');

        if (! File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $module) {
            $moduleName = basename($module);

            // Load module routes
            $this->loadModuleRoutes($moduleName);

            // Load module migrations
            $this->loadModuleMigrations($moduleName);

            // Load module views
            $this->loadModuleViews($moduleName);
        }
    }

    /**
     * Load module routes
     */
    protected function loadModuleRoutes(string $moduleName): void
    {
        $routesPath = base_path("modules/{$moduleName}/routes");

        if (File::exists("{$routesPath}/api.php")) {
            $this->loadRoutesFrom("{$routesPath}/api.php");
        }

        if (File::exists("{$routesPath}/web.php")) {
            $this->loadRoutesFrom("{$routesPath}/web.php");
        }
    }

    /**
     * Load module migrations
     */
    protected function loadModuleMigrations(string $moduleName): void
    {
        $migrationsPath = base_path("modules/{$moduleName}/database/migrations");

        if (File::exists($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * Load module views
     */
    protected function loadModuleViews(string $moduleName): void
    {
        $viewsPath = base_path("modules/{$moduleName}/resources/views");

        if (File::exists($viewsPath)) {
            $this->loadViewsFrom($viewsPath, strtolower($moduleName));
        }
    }
}
