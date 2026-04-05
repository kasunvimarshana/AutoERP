<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Notification\Application\Contracts\NotificationPreferenceServiceInterface;
use Modules\Notification\Application\Contracts\NotificationServiceInterface;
use Modules\Notification\Application\Contracts\NotificationTemplateServiceInterface;
use Modules\Notification\Application\Contracts\SendNotificationServiceInterface;
use Modules\Notification\Application\Services\NotificationPreferenceService;
use Modules\Notification\Application\Services\NotificationService;
use Modules\Notification\Application\Services\NotificationTemplateService;
use Modules\Notification\Application\Services\SendNotificationService;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationPreferenceRepositoryInterface;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationRepositoryInterface;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationTemplateRepositoryInterface;
use Modules\Notification\Infrastructure\Channels\Drivers\DatabaseChannelDriver;
use Modules\Notification\Infrastructure\Channels\Drivers\EmailChannelDriver;
use Modules\Notification\Infrastructure\Channels\Drivers\PushChannelDriver;
use Modules\Notification\Infrastructure\Channels\Drivers\SmsChannelDriver;
use Modules\Notification\Infrastructure\Channels\NotificationChannelDispatcher;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationModel;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationPreferenceModel;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationTemplateModel;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationPreferenceRepository;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationRepository;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationTemplateRepository;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Channel Dispatcher ─────────────────────────────────────────────────
        $this->app->singleton(NotificationChannelDispatcher::class, function () {
            $dispatcher = new NotificationChannelDispatcher();
            $dispatcher->addDriver('database', new DatabaseChannelDriver());
            $dispatcher->addDriver('email',    new EmailChannelDriver());
            $dispatcher->addDriver('sms',      new SmsChannelDriver());
            $dispatcher->addDriver('push',     new PushChannelDriver());
            return $dispatcher;
        });

        // ── Repository Bindings ────────────────────────────────────────────────
        $this->app->bind(NotificationRepositoryInterface::class, fn($app) =>
            new EloquentNotificationRepository($app->make(NotificationModel::class))
        );

        $this->app->bind(NotificationTemplateRepositoryInterface::class, fn($app) =>
            new EloquentNotificationTemplateRepository($app->make(NotificationTemplateModel::class))
        );

        $this->app->bind(NotificationPreferenceRepositoryInterface::class, fn($app) =>
            new EloquentNotificationPreferenceRepository($app->make(NotificationPreferenceModel::class))
        );

        // ── Service Bindings ───────────────────────────────────────────────────
        $this->app->bind(NotificationServiceInterface::class, fn($app) =>
            new NotificationService($app->make(NotificationRepositoryInterface::class))
        );

        $this->app->bind(NotificationTemplateServiceInterface::class, fn($app) =>
            new NotificationTemplateService($app->make(NotificationTemplateRepositoryInterface::class))
        );

        $this->app->bind(NotificationPreferenceServiceInterface::class, fn($app) =>
            new NotificationPreferenceService($app->make(NotificationPreferenceRepositoryInterface::class))
        );

        $this->app->bind(SendNotificationServiceInterface::class, fn($app) =>
            new SendNotificationService(
                $app->make(NotificationRepositoryInterface::class),
                $app->make(NotificationTemplateRepositoryInterface::class),
                $app->make(NotificationPreferenceRepositoryInterface::class),
                $app->make(NotificationChannelDispatcher::class),
            )
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
