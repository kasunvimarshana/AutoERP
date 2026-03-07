<?php

namespace App\Providers;

use App\Events\OrderCancelled;
use App\Events\OrderCompleted;
use App\Listeners\Order\HandleOrderCancelled;
use App\Listeners\Order\HandleOrderCompleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCompleted::class => [HandleOrderCompleted::class],
        OrderCancelled::class => [HandleOrderCancelled::class],
    ];
}
