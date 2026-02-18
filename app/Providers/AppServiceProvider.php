<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module service providers
        $this->registerModules();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register all module service providers.
     */
    protected function registerModules(): void
    {
        $modules = [
            'Core' => \Modules\Core\Providers\CoreServiceProvider::class,
            'Tenancy' => \Modules\Tenancy\Providers\TenancyServiceProvider::class,
            'IAM' => \Modules\IAM\Providers\IAMServiceProvider::class,
            'Sales' => \Modules\Sales\Providers\SalesServiceProvider::class,
            'Inventory' => \Modules\Inventory\Providers\InventoryServiceProvider::class,
            'Accounting' => \Modules\Accounting\Providers\AccountingServiceProvider::class,
            'HR' => \Modules\HR\Providers\HRServiceProvider::class,
            'Procurement' => \Modules\Procurement\Providers\ProcurementServiceProvider::class,
        ];

        foreach ($modules as $name => $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }
}
