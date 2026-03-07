<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Listeners\PublishUserCreated;
use App\Listeners\PublishUserDeleted;
use App\Listeners\PublishUserUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserCreated::class => [
            PublishUserCreated::class,
        ],
        UserUpdated::class => [
            PublishUserUpdated::class,
        ],
        UserDeleted::class => [
            PublishUserDeleted::class,
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
