<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Dispatch\Application\Contracts\CancelDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\ConfirmDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\CreateDispatchLineServiceInterface;
use Modules\Dispatch\Application\Contracts\CreateDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\DeleteDispatchLineServiceInterface;
use Modules\Dispatch\Application\Contracts\DeleteDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\DeliverDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\FindDispatchLineServiceInterface;
use Modules\Dispatch\Application\Contracts\FindDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\ShipDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\UpdateDispatchLineServiceInterface;
use Modules\Dispatch\Application\Contracts\UpdateDispatchServiceInterface;
use Modules\Dispatch\Application\Services\CancelDispatchService;
use Modules\Dispatch\Application\Services\ConfirmDispatchService;
use Modules\Dispatch\Application\Services\CreateDispatchLineService;
use Modules\Dispatch\Application\Services\CreateDispatchService;
use Modules\Dispatch\Application\Services\DeleteDispatchLineService;
use Modules\Dispatch\Application\Services\DeleteDispatchService;
use Modules\Dispatch\Application\Services\DeliverDispatchService;
use Modules\Dispatch\Application\Services\FindDispatchLineService;
use Modules\Dispatch\Application\Services\FindDispatchService;
use Modules\Dispatch\Application\Services\ShipDispatchService;
use Modules\Dispatch\Application\Services\UpdateDispatchLineService;
use Modules\Dispatch\Application\Services\UpdateDispatchService;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchLineRepositoryInterface;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchLineModel;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchModel;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories\EloquentDispatchLineRepository;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories\EloquentDispatchRepository;

class DispatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- Repositories ---
        $this->app->bind(DispatchRepositoryInterface::class, fn ($app) =>
            new EloquentDispatchRepository($app->make(DispatchModel::class)));

        $this->app->bind(DispatchLineRepositoryInterface::class, fn ($app) =>
            new EloquentDispatchLineRepository($app->make(DispatchLineModel::class)));

        // --- Services: Dispatch ---
        $this->app->bind(CreateDispatchServiceInterface::class, fn ($app) =>
            new CreateDispatchService($app->make(DispatchRepositoryInterface::class)));

        $this->app->bind(FindDispatchServiceInterface::class, fn ($app) =>
            new FindDispatchService($app->make(DispatchRepositoryInterface::class)));

        $this->app->bind(UpdateDispatchServiceInterface::class, fn ($app) =>
            new UpdateDispatchService($app->make(DispatchRepositoryInterface::class)));

        $this->app->bind(DeleteDispatchServiceInterface::class, fn ($app) =>
            new DeleteDispatchService($app->make(DispatchRepositoryInterface::class)));

        $this->app->bind(ConfirmDispatchServiceInterface::class, fn ($app) =>
            new ConfirmDispatchService($app->make(DispatchRepositoryInterface::class)));

        $this->app->bind(ShipDispatchServiceInterface::class, fn ($app) =>
            new ShipDispatchService($app->make(DispatchRepositoryInterface::class)));

        $this->app->bind(DeliverDispatchServiceInterface::class, fn ($app) =>
            new DeliverDispatchService($app->make(DispatchRepositoryInterface::class)));

        $this->app->bind(CancelDispatchServiceInterface::class, fn ($app) =>
            new CancelDispatchService($app->make(DispatchRepositoryInterface::class)));

        // --- Services: DispatchLine ---
        $this->app->bind(CreateDispatchLineServiceInterface::class, fn ($app) =>
            new CreateDispatchLineService($app->make(DispatchLineRepositoryInterface::class)));

        $this->app->bind(FindDispatchLineServiceInterface::class, fn ($app) =>
            new FindDispatchLineService($app->make(DispatchLineRepositoryInterface::class)));

        $this->app->bind(UpdateDispatchLineServiceInterface::class, fn ($app) =>
            new UpdateDispatchLineService($app->make(DispatchLineRepositoryInterface::class)));

        $this->app->bind(DeleteDispatchLineServiceInterface::class, fn ($app) =>
            new DeleteDispatchLineService($app->make(DispatchLineRepositoryInterface::class)));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Route::middleware(['api', 'auth:api', 'resolve.tenant'])
            ->prefix('api')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
            });
    }
}
