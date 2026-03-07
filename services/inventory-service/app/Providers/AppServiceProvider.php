<?php

namespace App\Providers;

use App\Modules\Inventory\Events\InventoryUpdated;
use App\Modules\Inventory\Events\StockReserved;
use App\Modules\Inventory\Events\StockReleased;
use App\Modules\Inventory\Listeners\PublishInventoryUpdatedEvent;
use App\Modules\Inventory\Repositories\Interfaces\InventoryRepositoryInterface;
use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Inventory\Services\Interfaces\InventoryServiceInterface;
use App\Modules\Inventory\Services\InventoryService;
use App\Services\KeycloakService;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KeycloakService::class);
        $this->app->singleton(RabbitMQService::class);

        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
    }

    public function boot(): void
    {
        Event::listen(InventoryUpdated::class, PublishInventoryUpdatedEvent::class);
    }
}
