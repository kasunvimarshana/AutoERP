<?php

declare(strict_types=1);

namespace Modules\Notification\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Notification\Models\Notification;
use Modules\Notification\Models\NotificationChannel;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Policies\NotificationChannelPolicy;
use Modules\Notification\Policies\NotificationPolicy;
use Modules\Notification\Policies\NotificationTemplatePolicy;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module configuration
        $this->mergeConfigFrom(__DIR__.'/../Config/notification.php', 'notification');

        // Register repositories
        $this->app->singleton(\Modules\Notification\Repositories\NotificationRepository::class);
        $this->app->singleton(\Modules\Notification\Repositories\NotificationTemplateRepository::class);
        $this->app->singleton(\Modules\Notification\Repositories\NotificationChannelRepository::class);
        $this->app->singleton(\Modules\Notification\Repositories\NotificationLogRepository::class);

        // Register services
        $this->app->singleton(\Modules\Notification\Services\NotificationService::class);
        $this->app->singleton(\Modules\Notification\Services\TemplateService::class);
        $this->app->singleton(\Modules\Notification\Services\NotificationDispatcher::class);
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register policies
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(NotificationTemplate::class, NotificationTemplatePolicy::class);
        Gate::policy(NotificationChannel::class, NotificationChannelPolicy::class);

        // Register event listeners (if Audit module is available)
        if (config('audit.enabled', false)) {
            $this->registerEventListeners();
        }
    }

    /**
     * Register event listeners for audit logging.
     */
    private function registerEventListeners(): void
    {
        // Check if event listeners exist before registering
        if (class_exists(\Modules\Notification\Listeners\LogNotificationSent::class)) {
            Event::listen(
                \Modules\Notification\Events\NotificationSent::class,
                \Modules\Notification\Listeners\LogNotificationSent::class
            );
        }

        if (class_exists(\Modules\Notification\Listeners\LogNotificationFailed::class)) {
            Event::listen(
                \Modules\Notification\Events\NotificationFailed::class,
                \Modules\Notification\Listeners\LogNotificationFailed::class
            );
        }

        if (class_exists(\Modules\Notification\Listeners\LogTemplateCreated::class)) {
            Event::listen(
                \Modules\Notification\Events\TemplateCreated::class,
                \Modules\Notification\Listeners\LogTemplateCreated::class
            );
        }

        if (class_exists(\Modules\Notification\Listeners\LogChannelCreated::class)) {
            Event::listen(
                \Modules\Notification\Events\ChannelCreated::class,
                \Modules\Notification\Listeners\LogChannelCreated::class
            );
        }
    }
}
