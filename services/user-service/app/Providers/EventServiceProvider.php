<?php

namespace App\Providers;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Listeners\PublishUserEventToRabbitMQ;
use App\Listeners\SendUserCreatedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserCreated::class => [
            SendUserCreatedNotification::class,
            PublishUserEventToRabbitMQ::class,
        ],
        UserUpdated::class => [
            PublishUserEventToRabbitMQ::class,
        ],
        UserDeleted::class => [
            PublishUserEventToRabbitMQ::class,
        ],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
