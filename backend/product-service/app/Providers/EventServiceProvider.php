<?php

namespace App\Providers;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Listeners\Product\HandleProductCreated;
use App\Listeners\Product\HandleProductDeleted;
use App\Listeners\Product\HandleProductUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ProductCreated::class => [HandleProductCreated::class],
        ProductUpdated::class => [HandleProductUpdated::class],
        ProductDeleted::class => [HandleProductDeleted::class],
    ];
}
