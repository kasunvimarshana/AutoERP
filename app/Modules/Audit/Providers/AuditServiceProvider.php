<?php

declare(strict_types=1);

namespace Modules\Audit\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Models\AuditLogModel;
use Modules\Audit\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Repositories\EloquentAuditLogRepository;

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
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
