<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\GS1\Application\Contracts\CreateGs1BarcodeServiceInterface;
use Modules\GS1\Application\Contracts\CreateGs1IdentifierServiceInterface;
use Modules\GS1\Application\Contracts\DeleteGs1BarcodeServiceInterface;
use Modules\GS1\Application\Contracts\DeleteGs1IdentifierServiceInterface;
use Modules\GS1\Application\Contracts\FindGs1BarcodeServiceInterface;
use Modules\GS1\Application\Contracts\FindGs1IdentifierServiceInterface;
use Modules\GS1\Application\Contracts\UpdateGs1BarcodeServiceInterface;
use Modules\GS1\Application\Contracts\UpdateGs1IdentifierServiceInterface;
use Modules\GS1\Application\Services\CreateGs1BarcodeService;
use Modules\GS1\Application\Services\CreateGs1IdentifierService;
use Modules\GS1\Application\Services\DeleteGs1BarcodeService;
use Modules\GS1\Application\Services\DeleteGs1IdentifierService;
use Modules\GS1\Application\Services\FindGs1BarcodeService;
use Modules\GS1\Application\Services\FindGs1IdentifierService;
use Modules\GS1\Application\Services\UpdateGs1BarcodeService;
use Modules\GS1\Application\Services\UpdateGs1IdentifierService;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1BarcodeRepositoryInterface;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1IdentifierRepositoryInterface;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\Gs1BarcodeModel;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\Gs1IdentifierModel;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories\EloquentGs1BarcodeRepository;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories\EloquentGs1IdentifierRepository;

class GS1ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Gs1IdentifierRepositoryInterface::class, fn ($app) =>
            new EloquentGs1IdentifierRepository($app->make(Gs1IdentifierModel::class)));

        $this->app->bind(Gs1BarcodeRepositoryInterface::class, fn ($app) =>
            new EloquentGs1BarcodeRepository($app->make(Gs1BarcodeModel::class)));

        $this->app->bind(CreateGs1IdentifierServiceInterface::class, fn ($app) =>
            new CreateGs1IdentifierService($app->make(Gs1IdentifierRepositoryInterface::class)));

        $this->app->bind(FindGs1IdentifierServiceInterface::class, fn ($app) =>
            new FindGs1IdentifierService($app->make(Gs1IdentifierRepositoryInterface::class)));

        $this->app->bind(UpdateGs1IdentifierServiceInterface::class, fn ($app) =>
            new UpdateGs1IdentifierService($app->make(Gs1IdentifierRepositoryInterface::class)));

        $this->app->bind(DeleteGs1IdentifierServiceInterface::class, fn ($app) =>
            new DeleteGs1IdentifierService($app->make(Gs1IdentifierRepositoryInterface::class)));

        $this->app->bind(CreateGs1BarcodeServiceInterface::class, fn ($app) =>
            new CreateGs1BarcodeService($app->make(Gs1BarcodeRepositoryInterface::class)));

        $this->app->bind(FindGs1BarcodeServiceInterface::class, fn ($app) =>
            new FindGs1BarcodeService($app->make(Gs1BarcodeRepositoryInterface::class)));

        $this->app->bind(UpdateGs1BarcodeServiceInterface::class, fn ($app) =>
            new UpdateGs1BarcodeService($app->make(Gs1BarcodeRepositoryInterface::class)));

        $this->app->bind(DeleteGs1BarcodeServiceInterface::class, fn ($app) =>
            new DeleteGs1BarcodeService($app->make(Gs1BarcodeRepositoryInterface::class)));
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
