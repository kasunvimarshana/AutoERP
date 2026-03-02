<?php

declare(strict_types=1);

namespace Modules\Product\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Product\Application\Handlers\CreateProductHandler;
use Modules\Product\Application\Handlers\UpdateProductHandler;
use Modules\Product\Application\Services\BarcodeService;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Repositories\ProductRepository;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        $this->app->singleton(CreateProductHandler::class);
        $this->app->singleton(UpdateProductHandler::class);
        $this->app->singleton(BarcodeService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
    }
}
