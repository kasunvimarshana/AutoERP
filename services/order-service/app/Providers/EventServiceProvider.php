<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\OrderCancelled;
use App\Events\OrderCompleted;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Listeners\HandleInventoryRelease;
use App\Listeners\HandleInventoryReservation;
use App\Listeners\PublishOrderCancelled;
use App\Listeners\PublishOrderCreated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderCreated::class => [
            PublishOrderCreated::class,
        ],
        OrderUpdated::class => [
            // PublishOrderUpdated could be added here if needed
        ],
        OrderCancelled::class => [
            HandleInventoryRelease::class,
            PublishOrderCancelled::class,
        ],
        OrderCompleted::class => [
            // Additional listeners (e.g., send delivery confirmation email)
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
