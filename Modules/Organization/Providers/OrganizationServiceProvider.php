<?php

declare(strict_types=1);

namespace Modules\Organization\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Organization\Repositories\Contracts\LocationRepositoryInterface;
use Modules\Organization\Repositories\Contracts\OrganizationRepositoryInterface;
use Modules\Organization\Repositories\Contracts\OrganizationalUnitRepositoryInterface;
use Modules\Organization\Repositories\LocationRepository;
use Modules\Organization\Repositories\OrganizationRepository;
use Modules\Organization\Repositories\OrganizationalUnitRepository;

class OrganizationServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $moduleName = 'Organization';

    /**
     * @var string
     */
    protected $moduleNameLower = 'organization';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerRepositories();
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'Resources/lang'));
        }
    }

    /**
     * Register repositories.
     */
    protected function registerRepositories(): void
    {
        $this->app->bind(
            OrganizationRepositoryInterface::class,
            OrganizationRepository::class
        );

        $this->app->bind(
            LocationRepositoryInterface::class,
            LocationRepository::class
        );

        $this->app->bind(
            OrganizationalUnitRepositoryInterface::class,
            OrganizationalUnitRepository::class
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
