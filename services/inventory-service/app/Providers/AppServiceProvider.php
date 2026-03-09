<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Domain\Contracts\ProductServiceInterface;
use App\Domain\Contracts\StockServiceInterface;
use App\Services\ProductService;
use App\Services\StockService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(StockServiceInterface::class, StockService::class);
    }
    public function boot(): void {}
}
