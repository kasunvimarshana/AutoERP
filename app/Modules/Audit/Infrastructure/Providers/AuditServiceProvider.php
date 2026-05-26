<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\CaptureSystemEventAuditServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\CreateAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\GetAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\ListAuditLogsServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\LogActivityServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\LogEntityChangeServiceInterface;
use Modules\Audit\Application\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Application\UseCases\AuditLogs\CaptureSystemEventAuditService;
use Modules\Audit\Application\UseCases\AuditLogs\CreateAuditLogService;
use Modules\Audit\Application\UseCases\AuditLogs\GetAuditLogService;
use Modules\Audit\Application\UseCases\AuditLogs\ListAuditLogsService;
use Modules\Audit\Application\UseCases\AuditLogs\LogActivityService;
use Modules\Audit\Application\UseCases\AuditLogs\LogEntityChangeService;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogRepository;
use Modules\Audit\Infrastructure\Subscribers\AuditEventSubscriber;

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
                LogActivityServiceInterface::class => LogActivityService::class,
                LogEntityChangeServiceInterface::class => LogEntityChangeService::class,
                CaptureSystemEventAuditServiceInterface::class => CaptureSystemEventAuditService::class,
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

        Event::subscribe(AuditEventSubscriber::class);
    }
}
