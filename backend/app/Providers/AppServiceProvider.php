<?php

namespace App\Providers;

use App\Core\ModuleLoader;
use App\Core\EventBus;
use App\Core\ConfigurationManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register core services as singletons
        $this->app->singleton(ModuleLoader::class, function ($app) {
            $loader = new ModuleLoader();
            $loader->discover();
            return $loader;
        });

        $this->app->singleton(EventBus::class, function ($app) {
            return new EventBus();
        });

        $this->app->singleton(ConfigurationManager::class, function ($app) {
            $manager = new ConfigurationManager();
            // Load default configuration
            $manager->load(config('erp', []));
            return $manager;
        });

        // Register aliases
        $this->app->alias(ModuleLoader::class, 'module.loader');
        $this->app->alias(EventBus::class, 'event.bus');
        $this->app->alias(ConfigurationManager::class, 'config.manager');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load all enabled modules
        $moduleLoader = $this->app->make(ModuleLoader::class);
        $moduleLoader->loadAll();
    }
}
