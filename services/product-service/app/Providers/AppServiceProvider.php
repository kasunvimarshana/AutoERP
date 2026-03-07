<?php

namespace App\Providers;

use App\Modules\Product\Events\ProductCreated;
use App\Modules\Product\Events\ProductDeleted;
use App\Modules\Product\Events\ProductUpdated;
use App\Modules\Product\Listeners\PublishProductCreatedEvent;
use App\Modules\Product\Listeners\PublishProductDeletedEvent;
use App\Modules\Product\Listeners\PublishProductUpdatedEvent;
use App\Modules\Product\Repositories\Interfaces\ProductRepositoryInterface;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Services\Interfaces\ProductServiceInterface;
use App\Modules\Product\Services\ProductService;
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

        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
    }

    public function boot(): void
    {
        Event::listen(ProductCreated::class, PublishProductCreatedEvent::class);
        Event::listen(ProductUpdated::class, PublishProductUpdatedEvent::class);
        Event::listen(ProductDeleted::class, PublishProductDeletedEvent::class);
    }
}
