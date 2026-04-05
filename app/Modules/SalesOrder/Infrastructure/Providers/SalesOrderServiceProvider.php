<?php
declare(strict_types=1);
namespace Modules\SalesOrder\Infrastructure\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\SalesOrder\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPackingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPickingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Services\ConfirmSalesOrderService;
use Modules\SalesOrder\Application\Services\CreateSalesOrderService;
use Modules\SalesOrder\Application\Services\StartPackingSalesOrderService;
use Modules\SalesOrder\Application\Services\StartPickingSalesOrderService;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderRepository;
class SalesOrderServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(SalesOrderRepositoryInterface::class, fn($app) =>
            new EloquentSalesOrderRepository($app->make(SalesOrderModel::class),$app->make(SalesOrderLineModel::class))
        );
        $this->app->bind(CreateSalesOrderServiceInterface::class, fn($app) => new CreateSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));
        $this->app->bind(ConfirmSalesOrderServiceInterface::class, fn($app) => new ConfirmSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));
        $this->app->bind(StartPickingSalesOrderServiceInterface::class, fn($app) => new StartPickingSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));
        $this->app->bind(StartPackingSalesOrderServiceInterface::class, fn($app) => new StartPackingSalesOrderService($app->make(SalesOrderRepositoryInterface::class)));
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
