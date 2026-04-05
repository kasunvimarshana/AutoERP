<?php
declare(strict_types=1);
namespace Modules\Audit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\Contracts\QueryAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\RecordAuditLogServiceInterface;
use Modules\Audit\Application\Services\QueryAuditLogService;
use Modules\Audit\Application\Services\RecordAuditLogService;
use Modules\Audit\Domain\RepositoryInterfaces\AuditLogRepositoryInterface;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogRepository;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogRepositoryInterface::class, fn($app) =>
            new EloquentAuditLogRepository($app->make(AuditLogModel::class))
        );

        $this->app->bind(RecordAuditLogServiceInterface::class, fn($app) =>
            new RecordAuditLogService($app->make(AuditLogRepositoryInterface::class))
        );
        $this->app->bind(QueryAuditLogServiceInterface::class, fn($app) =>
            new QueryAuditLogService($app->make(AuditLogRepositoryInterface::class))
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
