<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Product\Services\ProductService;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Messaging\EventPublisher;
use App\Infrastructure\Persistence\Models\Product;
use App\Infrastructure\Persistence\Repositories\ProductRepository;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider — Product Service DI bindings.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(ProductRepositoryInterface::class, function ($app) {
            return new ProductRepository($app->make(Product::class));
        });

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

        // ProductService
        $this->app->singleton(ProductService::class, function ($app) {
            return new ProductService(
                $app->make(ProductRepositoryInterface::class),
                $app->make(EventPublisher::class),
            );
        });
    }

    public function boot(): void {}
}
