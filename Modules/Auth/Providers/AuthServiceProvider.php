<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use Modules\Auth\Infrastructure\Repositories\UserRepository;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->singleton(
            \Modules\Auth\Application\Handlers\LoginHandler::class,
            fn ($app) => new \Modules\Auth\Application\Handlers\LoginHandler(
                $app->make(UserRepositoryInterface::class)
            )
        );

        $this->app->singleton(
            \Modules\Auth\Application\Handlers\RegisterHandler::class,
            fn ($app) => new \Modules\Auth\Application\Handlers\RegisterHandler(
                $app->make(UserRepositoryInterface::class)
            )
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
    }
}
