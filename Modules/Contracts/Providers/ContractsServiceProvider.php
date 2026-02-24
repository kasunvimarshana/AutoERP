<?php

namespace Modules\Contracts\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Contracts\Domain\Contracts\ContractRepositoryInterface;
use Modules\Contracts\Infrastructure\Repositories\ContractRepository;

class ContractsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContractRepositoryInterface::class, ContractRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'contracts');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
