<?php

namespace Modules\Sales\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sales\Application\Repositories\GdnHeaderRepositoryInterface;
use Modules\Sales\Application\Repositories\GdnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentGdnHeaderRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentGdnLineRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderLineRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesOrderRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesReturnLineRepository;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesReturnRepository;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            GdnHeaderRepositoryInterface::class => EloquentGdnHeaderRepository::class,
            GdnLineRepositoryInterface::class => EloquentGdnLineRepository::class,
            SalesOrderLineRepositoryInterface::class => EloquentSalesOrderLineRepository::class,
            SalesOrderRepositoryInterface::class => EloquentSalesOrderRepository::class,
            SalesReturnLineRepositoryInterface::class => EloquentSalesReturnLineRepository::class,
            SalesReturnRepositoryInterface::class => EloquentSalesReturnRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
