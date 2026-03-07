<?php

namespace App\Providers;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Listeners\PublishProductCreatedEvent;
use App\Listeners\PublishProductDeletedEvent;
use App\Listeners\PublishProductUpdatedEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * EventServiceProvider
 *
 * Maps domain events to their listeners.
 * Each listener publishes to RabbitMQ so other services can react.
 *
 * Event Flow:
 *   ProductCreated → PublishProductCreatedEvent → RabbitMQ "product.created"
 *   ProductUpdated → PublishProductUpdatedEvent → RabbitMQ "product.updated"
 *   ProductDeleted → PublishProductDeletedEvent → RabbitMQ "product.deleted"
 *
 * Other services (Node.js, Python, Java) subscribe to the RabbitMQ exchange
 * with their own queues and routing key bindings.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ProductCreated::class => [
            PublishProductCreatedEvent::class,
        ],
        ProductUpdated::class => [
            PublishProductUpdatedEvent::class,
        ],
        ProductDeleted::class => [
            PublishProductDeletedEvent::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
