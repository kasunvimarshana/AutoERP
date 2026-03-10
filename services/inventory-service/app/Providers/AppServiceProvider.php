<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Inventory\Services\InventoryService;
use App\Infrastructure\Messaging\EventPublisher;
use App\Infrastructure\Persistence\Models\InventoryItem;
use App\Infrastructure\Persistence\Repositories\InventoryRepository;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider — Inventory Service DI bindings.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // EventPublisher
        $this->app->singleton(EventPublisher::class, function ($app) {
            return new EventPublisher(
                host:     (string) config('rabbitmq.host',     'rabbitmq'),
                port:     (int)    config('rabbitmq.port',     5672),
                user:     (string) config('rabbitmq.user',     'guest'),
                password: (string) config('rabbitmq.password', 'guest'),
                vhost:    (string) config('rabbitmq.vhost',    'kvsaas'),
            );
        });

        // InventoryRepository
        $this->app->singleton(InventoryRepository::class, function ($app) {
            return new InventoryRepository($app->make(InventoryItem::class));
        });

        // InventoryService
        $this->app->singleton(InventoryService::class, function ($app) {
            return new InventoryService(
                $app->make(InventoryRepository::class),
                $app->make(EventPublisher::class),
            );
        });
    }

    public function boot(): void {}
}
