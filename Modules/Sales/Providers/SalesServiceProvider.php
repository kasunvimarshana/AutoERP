<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sales\Application\Services\SalesOrderService;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Infrastructure\Repositories\SalesOrderRepository;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalesOrderRepositoryInterface::class, SalesOrderRepository::class);

        $this->app->singleton(SalesOrderService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
