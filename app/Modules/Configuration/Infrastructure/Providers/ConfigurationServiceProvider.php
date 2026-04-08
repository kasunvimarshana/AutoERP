<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Application\Contracts\OrgUnitServiceInterface;
use Modules\Configuration\Application\Contracts\SettingServiceInterface;
use Modules\Configuration\Application\Services\OrgUnitService;
use Modules\Configuration\Application\Services\SettingService;
use Modules\Configuration\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;
use Modules\Configuration\Domain\RepositoryInterfaces\SettingRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrgUnitRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentSettingRepository;

class ConfigurationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SettingRepositoryInterface::class, EloquentSettingRepository::class);
        $this->app->bind(OrgUnitRepositoryInterface::class, EloquentOrgUnitRepository::class);
        $this->app->bind(SettingServiceInterface::class, SettingService::class);
        $this->app->bind(OrgUnitServiceInterface::class, OrgUnitService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
