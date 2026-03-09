<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Domain\Contracts\{ProductRepositoryInterface,CategoryRepositoryInterface,WarehouseRepositoryInterface,StockRepositoryInterface,StockMovementRepositoryInterface};
use App\Repositories\{ProductRepository,CategoryRepository,WarehouseRepository,StockRepository,StockMovementRepository};

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(StockRepositoryInterface::class, StockRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);
    }
    public function boot(): void {}
}
