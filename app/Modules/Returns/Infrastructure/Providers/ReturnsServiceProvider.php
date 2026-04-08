<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Returns\Application\Contracts\PurchaseReturnServiceInterface;
use Modules\Returns\Application\Contracts\ReturnLineServiceInterface;
use Modules\Returns\Application\Contracts\SalesReturnServiceInterface;
use Modules\Returns\Application\Services\PurchaseReturnService;
use Modules\Returns\Application\Services\ReturnLineService;
use Modules\Returns\Application\Services\SalesReturnService;
use Modules\Returns\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnLineRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseReturnRepository;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentReturnLineRepository;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesReturnRepository;

class ReturnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PurchaseReturnRepositoryInterface::class, EloquentPurchaseReturnRepository::class);
        $this->app->bind(SalesReturnRepositoryInterface::class, EloquentSalesReturnRepository::class);
        $this->app->bind(ReturnLineRepositoryInterface::class, EloquentReturnLineRepository::class);
        $this->app->bind(PurchaseReturnServiceInterface::class, PurchaseReturnService::class);
        $this->app->bind(SalesReturnServiceInterface::class, SalesReturnService::class);
        $this->app->bind(ReturnLineServiceInterface::class, ReturnLineService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
