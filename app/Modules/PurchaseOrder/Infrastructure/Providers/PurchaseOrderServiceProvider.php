<?php
namespace Modules\PurchaseOrder\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\PurchaseOrder\Application\Contracts\ApprovePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CancelPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Services\ApprovePurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\CancelPurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\CreatePurchaseOrderService;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderLineRepository;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderRepository;

class PurchaseOrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PurchaseOrderRepositoryInterface::class, EloquentPurchaseOrderRepository::class);
        $this->app->bind(PurchaseOrderLineRepositoryInterface::class, EloquentPurchaseOrderLineRepository::class);
        $this->app->bind(CreatePurchaseOrderServiceInterface::class, CreatePurchaseOrderService::class);
        $this->app->bind(ApprovePurchaseOrderServiceInterface::class, ApprovePurchaseOrderService::class);
        $this->app->bind(CancelPurchaseOrderServiceInterface::class, CancelPurchaseOrderService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
