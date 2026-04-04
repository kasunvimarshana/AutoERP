<?php
namespace Modules\Configuration\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\Configuration\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\Configuration\Application\Contracts\GetSettingGroupServiceInterface;
use Modules\Configuration\Application\Contracts\GetSettingServiceInterface;
use Modules\Configuration\Application\Contracts\OrgUnitTreeServiceInterface;
use Modules\Configuration\Application\Contracts\SetSettingServiceInterface;
use Modules\Configuration\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\Configuration\Application\Services\CreateOrganizationUnitService;
use Modules\Configuration\Application\Services\DeleteOrganizationUnitService;
use Modules\Configuration\Application\Services\GetSettingGroupService;
use Modules\Configuration\Application\Services\GetSettingService;
use Modules\Configuration\Application\Services\OrgUnitTreeService;
use Modules\Configuration\Application\Services\SetSettingService;
use Modules\Configuration\Application\Services\UpdateOrganizationUnitService;
use Modules\Configuration\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\Configuration\Domain\RepositoryInterfaces\SystemSettingRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentSystemSettingRepository;

class ConfigurationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SystemSettingRepositoryInterface::class, EloquentSystemSettingRepository::class);
        $this->app->bind(OrganizationUnitRepositoryInterface::class, EloquentOrganizationUnitRepository::class);
        $this->app->bind(GetSettingServiceInterface::class, GetSettingService::class);
        $this->app->bind(GetSettingGroupServiceInterface::class, GetSettingGroupService::class);
        $this->app->bind(SetSettingServiceInterface::class, SetSettingService::class);
        $this->app->bind(CreateOrganizationUnitServiceInterface::class, CreateOrganizationUnitService::class);
        $this->app->bind(UpdateOrganizationUnitServiceInterface::class, UpdateOrganizationUnitService::class);
        $this->app->bind(DeleteOrganizationUnitServiceInterface::class, DeleteOrganizationUnitService::class);
        $this->app->bind(OrgUnitTreeServiceInterface::class, OrgUnitTreeService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
