<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\User\Services\UserService;
use App\Infrastructure\Messaging\EventPublisher;
use App\Infrastructure\Persistence\Models\UserProfile;
use App\Infrastructure\Persistence\Repositories\UserProfileRepository;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider — User Service DI bindings.
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

        // UserProfileRepository
        $this->app->singleton(UserProfileRepository::class, function ($app) {
            return new UserProfileRepository($app->make(UserProfile::class));
        });

        // UserService
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService(
                $app->make(UserProfileRepository::class),
                $app->make(EventPublisher::class),
            );
        });
    }

    public function boot(): void {}
}
