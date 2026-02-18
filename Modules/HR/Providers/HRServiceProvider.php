<?php

namespace Modules\HR\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\HR\Repositories\AttendanceRepository;
use Modules\HR\Repositories\Contracts\AttendanceRepositoryInterface;
use Modules\HR\Repositories\Contracts\DepartmentRepositoryInterface;
use Modules\HR\Repositories\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Repositories\Contracts\LeaveRepositoryInterface;
use Modules\HR\Repositories\Contracts\LeaveTypeRepositoryInterface;
use Modules\HR\Repositories\Contracts\PayrollRepositoryInterface;
use Modules\HR\Repositories\Contracts\PerformanceReviewRepositoryInterface;
use Modules\HR\Repositories\Contracts\PositionRepositoryInterface;
use Modules\HR\Repositories\DepartmentRepository;
use Modules\HR\Repositories\EmployeeRepository;
use Modules\HR\Repositories\LeaveRepository;
use Modules\HR\Repositories\LeaveTypeRepository;
use Modules\HR\Repositories\PayrollRepository;
use Modules\HR\Repositories\PerformanceReviewRepository;
use Modules\HR\Repositories\PositionRepository;

class HRServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $moduleName = 'HR';

    /**
     * @var string
     */
    protected $moduleNameLower = 'hr';

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
            EmployeeRepositoryInterface::class,
            EmployeeRepository::class
        );

        $this->app->bind(
            DepartmentRepositoryInterface::class,
            DepartmentRepository::class
        );

        $this->app->bind(
            PositionRepositoryInterface::class,
            PositionRepository::class
        );

        $this->app->bind(
            AttendanceRepositoryInterface::class,
            AttendanceRepository::class
        );

        $this->app->bind(
            LeaveRepositoryInterface::class,
            LeaveRepository::class
        );

        $this->app->bind(
            LeaveTypeRepositoryInterface::class,
            LeaveTypeRepository::class
        );

        $this->app->bind(
            PayrollRepositoryInterface::class,
            PayrollRepository::class
        );

        $this->app->bind(
            PerformanceReviewRepositoryInterface::class,
            PerformanceReviewRepository::class
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
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
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

    /**
     * Get publishable view paths.
     */
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
