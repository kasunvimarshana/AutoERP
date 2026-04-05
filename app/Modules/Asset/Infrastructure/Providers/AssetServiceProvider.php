<?php
declare(strict_types=1);
namespace Modules\Asset\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Asset\Application\Services\ManageFixedAssetService;
use Modules\Asset\Application\Services\RecordDepreciationService;
use Modules\Asset\Domain\RepositoryInterfaces\AssetDepreciationRepositoryInterface;
use Modules\Asset\Domain\RepositoryInterfaces\FixedAssetRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\AssetDepreciationModel;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\FixedAssetModel;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories\EloquentAssetDepreciationRepository;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories\EloquentFixedAssetRepository;

class AssetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FixedAssetRepositoryInterface::class, fn($app) =>
            new EloquentFixedAssetRepository($app->make(FixedAssetModel::class))
        );
        $this->app->bind(AssetDepreciationRepositoryInterface::class, fn($app) =>
            new EloquentAssetDepreciationRepository($app->make(AssetDepreciationModel::class))
        );
        $this->app->bind(ManageFixedAssetService::class, fn($app) =>
            new ManageFixedAssetService($app->make(FixedAssetRepositoryInterface::class))
        );
        $this->app->bind(RecordDepreciationService::class, fn($app) =>
            new RecordDepreciationService(
                $app->make(FixedAssetRepositoryInterface::class),
                $app->make(AssetDepreciationRepositoryInterface::class),
            )
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
