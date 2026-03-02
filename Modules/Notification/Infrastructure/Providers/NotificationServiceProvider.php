<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Notification\Domain\Contracts\NotificationRepositoryContract;
use Modules\Notification\Infrastructure\Repositories\NotificationRepository;

/**
 * Notification module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            NotificationRepositoryContract::class,
            NotificationRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../Database/Migrations'
        );

        $this->loadRoutesFrom(
            __DIR__.'/../../routes/api.php'
        );
    }
}
