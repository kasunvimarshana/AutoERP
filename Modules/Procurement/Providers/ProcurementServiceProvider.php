<?php

namespace Modules\Procurement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Procurement\Repositories\Contracts\GoodsReceiptRepositoryInterface;
use Modules\Procurement\Repositories\Contracts\PurchaseOrderLineRepositoryInterface;
use Modules\Procurement\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Procurement\Repositories\Contracts\PurchaseRequisitionLineRepositoryInterface;
use Modules\Procurement\Repositories\Contracts\PurchaseRequisitionRepositoryInterface;
use Modules\Procurement\Repositories\Contracts\SupplierRepositoryInterface;
use Modules\Procurement\Repositories\GoodsReceiptRepository;
use Modules\Procurement\Repositories\PurchaseOrderLineRepository;
use Modules\Procurement\Repositories\PurchaseOrderRepository;
use Modules\Procurement\Repositories\PurchaseRequisitionLineRepository;
use Modules\Procurement\Repositories\PurchaseRequisitionRepository;
use Modules\Procurement\Repositories\SupplierRepository;

class ProcurementServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $moduleName = 'Procurement';

    /**
     * @var string
     */
    protected $moduleNameLower = 'procurement';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        // Register repositories
        $this->app->bind(
            SupplierRepositoryInterface::class,
            SupplierRepository::class
        );

        $this->app->bind(
            PurchaseRequisitionRepositoryInterface::class,
            PurchaseRequisitionRepository::class
        );

        $this->app->bind(
            PurchaseRequisitionLineRepositoryInterface::class,
            PurchaseRequisitionLineRepository::class
        );

        $this->app->bind(
            PurchaseOrderRepositoryInterface::class,
            PurchaseOrderRepository::class
        );

        $this->app->bind(
            PurchaseOrderLineRepositoryInterface::class,
            PurchaseOrderLineRepository::class
        );

        $this->app->bind(
            GoodsReceiptRepositoryInterface::class,
            GoodsReceiptRepository::class
        );
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
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
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
