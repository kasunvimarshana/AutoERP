<?php

namespace Modules\FieldService\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\FieldService\Domain\Contracts\ServiceOrderRepositoryInterface;
use Modules\FieldService\Domain\Contracts\ServiceTeamRepositoryInterface;
use Modules\FieldService\Infrastructure\Repositories\ServiceOrderRepository;
use Modules\FieldService\Infrastructure\Repositories\ServiceTeamRepository;

class FieldServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ServiceTeamRepositoryInterface::class, ServiceTeamRepository::class);
        $this->app->bind(ServiceOrderRepositoryInterface::class, ServiceOrderRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'fieldservice');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
