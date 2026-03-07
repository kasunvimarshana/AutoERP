<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\InventoryRepositoryInterface;
use App\Contracts\ProductServiceInterface;
use App\Repositories\InventoryRepository;
use App\Services\ProductService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
    }

    public function boot(): void {}
}
