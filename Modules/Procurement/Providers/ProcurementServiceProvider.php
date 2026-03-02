<?php

declare(strict_types=1);

namespace Modules\Procurement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Procurement\Application\Services\PurchaseOrderService;
use Modules\Procurement\Application\Services\SupplierService;
use Modules\Procurement\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Procurement\Domain\Contracts\SupplierRepositoryInterface;
use Modules\Procurement\Infrastructure\Repositories\PurchaseOrderRepository;
use Modules\Procurement\Infrastructure\Repositories\SupplierRepository;

class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->bind(PurchaseOrderRepositoryInterface::class, PurchaseOrderRepository::class);

        $this->app->singleton(SupplierService::class);
        $this->app->singleton(PurchaseOrderService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
