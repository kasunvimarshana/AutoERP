<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\CreateAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\DeleteAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\GetAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\ListAuditLogsServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\UpdateAuditLogServiceInterface;
use Modules\Audit\Application\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Application\UseCases\AuditLogs\CreateAuditLogService;
use Modules\Audit\Application\UseCases\AuditLogs\DeleteAuditLogService;
use Modules\Audit\Application\UseCases\AuditLogs\GetAuditLogService;
use Modules\Audit\Application\UseCases\AuditLogs\ListAuditLogsService;
use Modules\Audit\Application\UseCases\AuditLogs\UpdateAuditLogService;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogRepository;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/audit.php', 'audit');

        foreach (
            [
                ListAuditLogsServiceInterface::class => ListAuditLogsService::class,
                GetAuditLogServiceInterface::class => GetAuditLogService::class,
                CreateAuditLogServiceInterface::class => CreateAuditLogService::class,
                UpdateAuditLogServiceInterface::class => UpdateAuditLogService::class,
                DeleteAuditLogServiceInterface::class => DeleteAuditLogService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(AuditLogRepositoryInterface::class, function (): AuditLogRepositoryInterface {
            return new EloquentAuditLogRepository(new AuditLogModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}