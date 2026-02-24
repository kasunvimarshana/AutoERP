<?php
namespace Modules\Notification\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Notification\Domain\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Infrastructure\Repositories\NotificationRepository;
class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'notification');
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
