<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\InventoryDepleted;
use App\Events\InventoryLow;
use App\Events\InventoryUpdated;
use App\Listeners\PublishInventoryLow;
use App\Listeners\PublishInventoryUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        InventoryUpdated::class => [
            PublishInventoryUpdated::class,
        ],
        InventoryLow::class => [
            PublishInventoryLow::class,
        ],
        InventoryDepleted::class => [
            // Add a dedicated publisher listener here if needed
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
