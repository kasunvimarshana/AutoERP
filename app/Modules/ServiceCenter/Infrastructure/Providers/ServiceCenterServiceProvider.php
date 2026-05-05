<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\ServiceCenter\Application\Contracts\ServiceJobServiceInterface;
use Modules\ServiceCenter\Application\Services\ServiceJobService;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceJobRepositoryInterface;
use Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Repositories\EloquentServiceJobRepository;

class ServiceCenterServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(ServiceJobRepositoryInterface::class, EloquentServiceJobRepository::class);
        $this->app->bind(ServiceJobServiceInterface::class, ServiceJobService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
