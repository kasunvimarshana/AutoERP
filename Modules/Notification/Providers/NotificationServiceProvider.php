<?php

declare(strict_types=1);

namespace Modules\Notification\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Notification\Application\Services\NotificationService;
use Modules\Notification\Application\Services\NotificationTemplateService;
use Modules\Notification\Domain\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Domain\Contracts\NotificationTemplateRepositoryInterface;
use Modules\Notification\Infrastructure\Repositories\NotificationRepository;
use Modules\Notification\Infrastructure\Repositories\NotificationTemplateRepository;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationTemplateRepositoryInterface::class, NotificationTemplateRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->singleton(NotificationTemplateService::class);
        $this->app->singleton(NotificationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
