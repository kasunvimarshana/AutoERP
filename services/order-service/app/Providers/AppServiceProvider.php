<?php

namespace App\Providers;

use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderCreated;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Events\OrderUpdated;
use App\Modules\Order\Listeners\PublishOrderCancelledEvent;
use App\Modules\Order\Listeners\PublishOrderCreatedEvent;
use App\Modules\Order\Listeners\PublishOrderStatusChangedEvent;
use App\Modules\Order\Listeners\PublishOrderUpdatedEvent;
use App\Modules\Order\Repositories\Interfaces\OrderRepositoryInterface;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Order\Services\Interfaces\OrderServiceInterface;
use App\Modules\Order\Services\OrderService;
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

        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
    }

    public function boot(): void
    {
        Event::listen(OrderCreated::class, PublishOrderCreatedEvent::class);
        Event::listen(OrderUpdated::class, PublishOrderUpdatedEvent::class);
        Event::listen(OrderStatusChanged::class, PublishOrderStatusChangedEvent::class);
        Event::listen(OrderCancelled::class, PublishOrderCancelledEvent::class);
    }
}
