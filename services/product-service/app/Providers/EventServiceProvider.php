<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Listeners\PublishProductCreated;
use App\Listeners\PublishProductDeleted;
use App\Listeners\PublishProductUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ProductCreated::class => [
            PublishProductCreated::class,
        ],
        ProductUpdated::class => [
            PublishProductUpdated::class,
        ],
        ProductDeleted::class => [
            PublishProductDeleted::class,
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
