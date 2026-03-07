<?php

namespace App\Providers;

use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\InventoryRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Repositories\WebhookRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class,     UserRepository::class);
        $this->app->bind(ProductRepository::class,  ProductRepository::class);
        $this->app->bind(InventoryRepository::class, InventoryRepository::class);
        $this->app->bind(OrderRepository::class,    OrderRepository::class);
        $this->app->bind(WebhookRepository::class,  WebhookRepository::class);
    }
}
