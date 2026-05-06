<?php

declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Asset\Application\Contracts\FindAssetAvailabilityServiceInterface;
use Modules\Asset\Application\Contracts\SyncAssetAvailabilityServiceInterface;
use Modules\Asset\Application\Services\FindAssetAvailabilityService;
use Modules\Asset\Application\Services\SyncAssetAvailabilityService;
use Modules\Asset\Domain\RepositoryInterfaces\AssetAvailabilityStateRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories\EloquentAssetAvailabilityStateRepository;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;

class AssetServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(
            AssetAvailabilityStateRepositoryInterface::class,
            EloquentAssetAvailabilityStateRepository::class,
        );
        $this->app->bind(SyncAssetAvailabilityServiceInterface::class, SyncAssetAvailabilityService::class);
        $this->app->bind(FindAssetAvailabilityServiceInterface::class, FindAssetAvailabilityService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
