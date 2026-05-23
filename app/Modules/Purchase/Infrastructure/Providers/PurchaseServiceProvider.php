<?php

namespace Modules\Purchase\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Purchase\Application\Repositories\GrnHeaderRepositoryInterface;
use Modules\Purchase\Application\Repositories\GrnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentGrnHeaderRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentGrnLineRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderLineRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseOrderRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseReturnLineRepository;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseReturnRepository;

class PurchaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            GrnHeaderRepositoryInterface::class => EloquentGrnHeaderRepository::class,
            GrnLineRepositoryInterface::class => EloquentGrnLineRepository::class,
            PurchaseOrderLineRepositoryInterface::class => EloquentPurchaseOrderLineRepository::class,
            PurchaseOrderRepositoryInterface::class => EloquentPurchaseOrderRepository::class,
            PurchaseReturnLineRepositoryInterface::class => EloquentPurchaseReturnLineRepository::class,
            PurchaseReturnRepositoryInterface::class => EloquentPurchaseReturnRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
