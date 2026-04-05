<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Infrastructure\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\PurchaseOrder\Application\Contracts\ConfirmPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Services\ConfirmPurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\CreatePurchaseOrderService;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderRepository;
class PurchaseOrderServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(PurchaseOrderRepositoryInterface::class, fn($app) =>
            new EloquentPurchaseOrderRepository($app->make(PurchaseOrderModel::class),$app->make(PurchaseOrderLineModel::class))
        );
        $this->app->bind(CreatePurchaseOrderServiceInterface::class, fn($app) => new CreatePurchaseOrderService($app->make(PurchaseOrderRepositoryInterface::class)));
        $this->app->bind(ConfirmPurchaseOrderServiceInterface::class, fn($app) => new ConfirmPurchaseOrderService($app->make(PurchaseOrderRepositoryInterface::class)));
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
