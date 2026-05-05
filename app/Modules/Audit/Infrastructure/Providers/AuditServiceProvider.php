<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\Contracts\AuditServiceInterface;
use Modules\Audit\Application\Services\AuditService;
use Modules\Audit\Domain\RepositoryInterfaces\AuditRepositoryInterface;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditRepository;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;

class AuditServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(AuditRepositoryInterface::class, EloquentAuditRepository::class);
        $this->app->bind(AuditServiceInterface::class, AuditService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
