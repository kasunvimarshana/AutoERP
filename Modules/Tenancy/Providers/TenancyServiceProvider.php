<?php

declare(strict_types=1);

namespace Modules\Tenancy\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Tenancy\Entities\Tenant;
use Modules\Tenancy\Policies\TenantPolicy;
use Modules\Tenancy\Repositories\Contracts\TenantRepositoryInterface;
use Modules\Tenancy\Repositories\TenantRepository;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * The module namespace.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Tenancy\Http\Controllers';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path('Tenancy', 'Database/Migrations'));
        $this->registerPolicies();
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
     * Register repositories.
     */
    protected function registerRepositories(): void
    {
        $this->app->bind(
            TenantRepositoryInterface::class,
            TenantRepository::class
        );
    }

    /**
     * Register policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Tenant::class, TenantPolicy::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path('Tenancy', 'Config/config.php') => config_path('tenancy.php'),
        ], 'tenancy-config');

        $this->mergeConfigFrom(
            module_path('Tenancy', 'Config/config.php'), 'tenancy'
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/tenancy');

        $sourcePath = module_path('Tenancy', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', 'tenancy-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), 'tenancy');
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/tenancy');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'tenancy');
        } else {
            $this->loadTranslationsFrom(module_path('Tenancy', 'Resources/lang'), 'tenancy');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get the publishable view paths.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path.'/modules/tenancy')) {
                $paths[] = $path.'/modules/tenancy';
            }
        }

        return $paths;
    }
}
