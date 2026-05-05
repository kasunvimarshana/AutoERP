<?php

declare(strict_types=1);

namespace Modules\Notifications\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Notifications\Application\Contracts\NotificationServiceInterface;
use Modules\Notifications\Application\Services\NotificationService;
use Modules\Notifications\Domain\RepositoryInterfaces\NotificationRepositoryInterface;
use Modules\Notifications\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationRepository;

class NotificationsServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
