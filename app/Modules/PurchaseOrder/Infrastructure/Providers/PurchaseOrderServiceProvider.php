<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\PurchaseOrder\Application\Contracts\ApprovePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CancelPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\DeletePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\DeletePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\FindPurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\FindPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\SubmitPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\UpdatePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Services\ApprovePurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\CancelPurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\CreatePurchaseOrderLineService;
use Modules\PurchaseOrder\Application\Services\CreatePurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\DeletePurchaseOrderLineService;
use Modules\PurchaseOrder\Application\Services\DeletePurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\FindPurchaseOrderLineService;
use Modules\PurchaseOrder\Application\Services\FindPurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\SubmitPurchaseOrderService;
use Modules\PurchaseOrder\Application\Services\UpdatePurchaseOrderLineService;
use Modules\PurchaseOrder\Application\Services\UpdatePurchaseOrderService;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderLineRepository;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderRepository;

class PurchaseOrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- Repositories ---
        $this->app->bind(PurchaseOrderRepositoryInterface::class, fn ($app) =>
            new EloquentPurchaseOrderRepository($app->make(PurchaseOrderModel::class)));

        $this->app->bind(PurchaseOrderLineRepositoryInterface::class, fn ($app) =>
            new EloquentPurchaseOrderLineRepository($app->make(PurchaseOrderLineModel::class)));

        // --- Services: PurchaseOrder ---
        $this->app->bind(CreatePurchaseOrderServiceInterface::class, fn ($app) =>
            new CreatePurchaseOrderService($app->make(PurchaseOrderRepositoryInterface::class)));

        $this->app->bind(FindPurchaseOrderServiceInterface::class, fn ($app) =>
            new FindPurchaseOrderService($app->make(PurchaseOrderRepositoryInterface::class)));

        $this->app->bind(UpdatePurchaseOrderServiceInterface::class, fn ($app) =>
            new UpdatePurchaseOrderService($app->make(PurchaseOrderRepositoryInterface::class)));

        $this->app->bind(DeletePurchaseOrderServiceInterface::class, fn ($app) =>
            new DeletePurchaseOrderService($app->make(PurchaseOrderRepositoryInterface::class)));

        $this->app->bind(SubmitPurchaseOrderServiceInterface::class, fn ($app) =>
            new SubmitPurchaseOrderService($app->make(PurchaseOrderRepositoryInterface::class)));

        $this->app->bind(ApprovePurchaseOrderServiceInterface::class, fn ($app) =>
            new ApprovePurchaseOrderService($app->make(PurchaseOrderRepositoryInterface::class)));

        $this->app->bind(CancelPurchaseOrderServiceInterface::class, fn ($app) =>
            new CancelPurchaseOrderService($app->make(PurchaseOrderRepositoryInterface::class)));

        // --- Services: PurchaseOrderLine ---
        $this->app->bind(CreatePurchaseOrderLineServiceInterface::class, fn ($app) =>
            new CreatePurchaseOrderLineService($app->make(PurchaseOrderLineRepositoryInterface::class)));

        $this->app->bind(FindPurchaseOrderLineServiceInterface::class, fn ($app) =>
            new FindPurchaseOrderLineService($app->make(PurchaseOrderLineRepositoryInterface::class)));

        $this->app->bind(UpdatePurchaseOrderLineServiceInterface::class, fn ($app) =>
            new UpdatePurchaseOrderLineService($app->make(PurchaseOrderLineRepositoryInterface::class)));

        $this->app->bind(DeletePurchaseOrderLineServiceInterface::class, fn ($app) =>
            new DeletePurchaseOrderLineService($app->make(PurchaseOrderLineRepositoryInterface::class)));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Route::middleware(['api', 'auth:api', 'resolve.tenant'])
            ->prefix('api')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
            });
    }
}
