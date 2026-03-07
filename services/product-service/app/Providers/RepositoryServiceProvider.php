<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\ProductRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            fn (): ProductRepository => new ProductRepository(new Product())
        );
    }

    public function boot(): void {}
}
