<?php

namespace Modules\AssetManagement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AssetManagement\Domain\Contracts\AssetCategoryRepositoryInterface;
use Modules\AssetManagement\Domain\Contracts\AssetRepositoryInterface;
use Modules\AssetManagement\Infrastructure\Repositories\AssetCategoryRepository;
use Modules\AssetManagement\Infrastructure\Repositories\AssetRepository;

class AssetManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AssetCategoryRepositoryInterface::class, AssetCategoryRepository::class);
        $this->app->bind(AssetRepositoryInterface::class, AssetRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'asset_management');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
