<?php

declare(strict_types=1);

namespace Modules\Audit\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Models\AuditLog;
use Modules\Audit\Repositories\AuditLogReaderInterface;
use Modules\Audit\Repositories\AuditLogWriterInterface;
use Modules\Audit\Repositories\EloquentAuditLogReader;
use Modules\Audit\Repositories\EloquentAuditLogWriter;
use Modules\Audit\Services\AuditRequestContextResolver;
use Modules\Audit\Services\RecordAuditEvent;
use Modules\Audit\Constants\AuditPermission;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/audit.php', 'audit');

        $this->app->singleton(
            AuditLogWriterInterface::class,
            fn (): AuditLogWriterInterface => new EloquentAuditLogWriter(new AuditLog()),
        );
        $this->app->singleton(
            AuditLogReaderInterface::class,
            fn (): AuditLogReaderInterface => new EloquentAuditLogReader(new AuditLog()),
        );
        $this->app->scoped(AuditRequestContextResolver::class);
        $this->app->scoped(AuditRecorderInterface::class, RecordAuditEvent::class);
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('audit', AuditPermission::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
