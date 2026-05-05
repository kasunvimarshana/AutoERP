<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Asset\Domain\RepositoryInterfaces\VehicleRepositoryInterface;
use Modules\Asset\Domain\RepositoryInterfaces\AssetRepositoryInterface;
use Modules\Asset\Domain\RepositoryInterfaces\AssetOwnerRepositoryInterface;
use Modules\Asset\Domain\RepositoryInterfaces\AssetDocumentRepositoryInterface;
use Modules\Asset\Domain\RepositoryInterfaces\AssetDepreciationRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRepository;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories\EloquentAssetRepository;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories\EloquentAssetOwnerRepository;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories\EloquentAssetDocumentRepository;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories\EloquentAssetDepreciationRepository;
use Modules\Asset\Application\Contracts\ManageAssetServiceInterface;
use Modules\Asset\Application\Contracts\ManageAssetOwnerServiceInterface;
use Modules\Asset\Application\Contracts\ManageVehicleServiceInterface;
use Modules\Asset\Application\Contracts\ManageAssetDocumentServiceInterface;
use Modules\Asset\Application\Contracts\ManageAssetDepreciationServiceInterface;
use Modules\Asset\Application\Services\ManageAssetService;
use Modules\Asset\Application\Services\ManageAssetOwnerService;
use Modules\Asset\Application\Services\ManageVehicleService;
use Modules\Asset\Application\Services\ManageAssetDocumentService;
use Modules\Asset\Application\Services\ManageAssetDepreciationService;

class AssetServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    /**
     * Register services
     *
     * Bind domain interfaces to their Eloquent implementations
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            VehicleRepositoryInterface::class,
            EloquentVehicleRepository::class
        );

        $this->app->bind(
            AssetRepositoryInterface::class,
            EloquentAssetRepository::class
        );

        $this->app->bind(
            AssetOwnerRepositoryInterface::class,
            EloquentAssetOwnerRepository::class
        );

        $this->app->bind(
            AssetDocumentRepositoryInterface::class,
            EloquentAssetDocumentRepository::class
        );

        $this->app->bind(
            AssetDepreciationRepositoryInterface::class,
            EloquentAssetDepreciationRepository::class
        );

        // Service bindings
        $this->app->bind(
            ManageAssetServiceInterface::class,
            ManageAssetService::class
        );

        $this->app->bind(
            ManageAssetOwnerServiceInterface::class,
            ManageAssetOwnerService::class
        );

        $this->app->bind(
            ManageVehicleServiceInterface::class,
            ManageVehicleService::class
        );

        $this->app->bind(
            ManageAssetDocumentServiceInterface::class,
            ManageAssetDocumentService::class
        );

        $this->app->bind(
            ManageAssetDepreciationServiceInterface::class,
            ManageAssetDepreciationService::class
        );
    }

    /**
     * Bootstrap services
     *
     * Load migrations and register routes
     */
    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
            '',
            []
        );
    }
}
