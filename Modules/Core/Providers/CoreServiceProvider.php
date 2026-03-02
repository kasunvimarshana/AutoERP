<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Domain\Contracts\TenantRepositoryInterface;
use Modules\Core\Infrastructure\Repositories\TenantRepository;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TenantRepositoryInterface::class,
            TenantRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
    }
}
