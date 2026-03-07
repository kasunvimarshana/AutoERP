<?php

namespace App\Providers;

use App\Repositories\InventoryRepository;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use App\Repositories\WarehouseRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
    }
}
