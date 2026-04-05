<?php
declare(strict_types=1);
namespace Modules\Contract\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Contract\Application\Services\ContractService;
use Modules\Contract\Domain\RepositoryInterfaces\ContractLineRepositoryInterface;
use Modules\Contract\Domain\RepositoryInterfaces\ContractRepositoryInterface;
use Modules\Contract\Infrastructure\Persistence\Eloquent\Models\ContractLineModel;
use Modules\Contract\Infrastructure\Persistence\Eloquent\Models\ContractModel;
use Modules\Contract\Infrastructure\Persistence\Eloquent\Repositories\EloquentContractLineRepository;
use Modules\Contract\Infrastructure\Persistence\Eloquent\Repositories\EloquentContractRepository;

class ContractServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContractRepositoryInterface::class, fn($app) =>
            new EloquentContractRepository($app->make(ContractModel::class))
        );
        $this->app->bind(ContractLineRepositoryInterface::class, fn($app) =>
            new EloquentContractLineRepository($app->make(ContractLineModel::class))
        );
        $this->app->bind(ContractService::class, fn($app) =>
            new ContractService($app->make(ContractRepositoryInterface::class))
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
