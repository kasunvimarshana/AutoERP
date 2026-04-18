<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\Contracts\AuditServiceInterface;
use Modules\Audit\Application\Services\AuditService;
use Modules\Audit\Domain\RepositoryInterfaces\AuditRepositoryInterface;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditRepository;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;

class AuditServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(AuditRepositoryInterface::class, function ($app) {
            return new EloquentAuditRepository($app->make(AuditLogModel::class));
        });

        $this->app->bind(AuditServiceInterface::class, function ($app) {
            return new AuditService($app->make(AuditRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
