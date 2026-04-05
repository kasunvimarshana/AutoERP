<?php
declare(strict_types=1);
namespace Modules\Returns\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Returns\Application\Contracts\CompleteReturnServiceInterface;
use Modules\Returns\Application\Contracts\ProcessReturnServiceInterface;
use Modules\Returns\Application\Contracts\RestockReturnServiceInterface;
use Modules\Returns\Application\Services\CompleteReturnService;
use Modules\Returns\Application\Services\ProcessReturnService;
use Modules\Returns\Application\Services\RestockReturnService;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnRequestRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\ReturnLineModel;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\ReturnRequestModel;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories\EloquentReturnRequestRepository;

class ReturnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReturnRequestRepositoryInterface::class, fn($app) =>
            new EloquentReturnRequestRepository(
                $app->make(ReturnRequestModel::class),
                $app->make(ReturnLineModel::class),
            )
        );
        $this->app->bind(ProcessReturnServiceInterface::class, fn($app) =>
            new ProcessReturnService($app->make(ReturnRequestRepositoryInterface::class))
        );
        $this->app->bind(RestockReturnServiceInterface::class, fn($app) =>
            new RestockReturnService($app->make(ReturnRequestRepositoryInterface::class))
        );
        $this->app->bind(CompleteReturnServiceInterface::class, fn($app) =>
            new CompleteReturnService($app->make(ReturnRequestRepositoryInterface::class))
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
