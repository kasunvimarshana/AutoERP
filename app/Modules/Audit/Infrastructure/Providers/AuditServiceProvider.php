<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogRepository;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/audit.php', 'audit');

        $this->app->singleton(AuditLogRepositoryInterface::class, function (): AuditLogRepositoryInterface {
            return new EloquentAuditLogRepository(new AuditLogModel);
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
