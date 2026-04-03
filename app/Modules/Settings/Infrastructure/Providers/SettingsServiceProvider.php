<?php

declare(strict_types=1);

namespace Modules\Settings\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Settings\Application\Contracts\CreateSettingServiceInterface;
use Modules\Settings\Application\Contracts\DeleteSettingServiceInterface;
use Modules\Settings\Application\Contracts\FindSettingServiceInterface;
use Modules\Settings\Application\Contracts\UpdateSettingServiceInterface;
use Modules\Settings\Application\Services\CreateSettingService;
use Modules\Settings\Application\Services\DeleteSettingService;
use Modules\Settings\Application\Services\FindSettingService;
use Modules\Settings\Application\Services\UpdateSettingService;
use Modules\Settings\Domain\RepositoryInterfaces\SettingRepositoryInterface;
use Modules\Settings\Infrastructure\Persistence\Eloquent\Models\SettingModel;
use Modules\Settings\Infrastructure\Persistence\Eloquent\Repositories\EloquentSettingRepository;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SettingRepositoryInterface::class, fn ($app) =>
            new EloquentSettingRepository($app->make(SettingModel::class)));

        $this->app->bind(CreateSettingServiceInterface::class, fn ($app) =>
            new CreateSettingService($app->make(SettingRepositoryInterface::class)));

        $this->app->bind(FindSettingServiceInterface::class, fn ($app) =>
            new FindSettingService($app->make(SettingRepositoryInterface::class)));

        $this->app->bind(UpdateSettingServiceInterface::class, fn ($app) =>
            new UpdateSettingService($app->make(SettingRepositoryInterface::class)));

        $this->app->bind(DeleteSettingServiceInterface::class, fn ($app) =>
            new DeleteSettingService($app->make(SettingRepositoryInterface::class)));
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
