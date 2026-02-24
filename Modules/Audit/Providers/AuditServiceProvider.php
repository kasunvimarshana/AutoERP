<?php
namespace Modules\Audit\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Audit\Domain\Contracts\AuditRepositoryInterface;
use Modules\Audit\Infrastructure\Repositories\AuditRepository;
class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'audit');
        $this->app->bind(AuditRepositoryInterface::class, AuditRepository::class);
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
