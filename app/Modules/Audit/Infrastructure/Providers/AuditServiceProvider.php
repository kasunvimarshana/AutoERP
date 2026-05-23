<?php

namespace Modules\Audit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogRepository;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            AuditLogRepositoryInterface::class => EloquentAuditLogRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
