<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\Contracts\AuditLogServiceInterface;
use Modules\Audit\Application\Services\AuditLogService;
use Modules\Audit\Domain\RepositoryInterfaces\AuditLogRepositoryInterface;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogRepository;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogRepositoryInterface::class, EloquentAuditLogRepository::class);
        $this->app->bind(AuditLogServiceInterface::class, AuditLogService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
