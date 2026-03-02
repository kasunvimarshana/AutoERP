<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Manufacturing\Application\Handlers\CreateBomHandler;
use Modules\Manufacturing\Application\Handlers\CreateProductionOrderHandler;
use Modules\Manufacturing\Application\Handlers\CompleteProductionOrderHandler;
use Modules\Manufacturing\Domain\Contracts\ManufacturingRepositoryInterface;
use Modules\Manufacturing\Infrastructure\Repositories\ManufacturingRepository;

class ManufacturingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ManufacturingRepositoryInterface::class,
            ManufacturingRepository::class
        );

        $this->app->singleton(CreateBomHandler::class);
        $this->app->singleton(CreateProductionOrderHandler::class);
        $this->app->singleton(CompleteProductionOrderHandler::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
    }
}
