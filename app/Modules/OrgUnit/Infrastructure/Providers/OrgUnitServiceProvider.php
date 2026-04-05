<?php
declare(strict_types=1);
namespace Modules\OrgUnit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\OrgUnit\Application\Services\OrgUnitService;
use Modules\OrgUnit\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;
use Modules\OrgUnit\Infrastructure\Persistence\Eloquent\Models\OrgUnitModel;
use Modules\OrgUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrgUnitRepository;

class OrgUnitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrgUnitRepositoryInterface::class, fn($app) =>
            new EloquentOrgUnitRepository($app->make(OrgUnitModel::class))
        );

        $this->app->bind(OrgUnitService::class, fn($app) =>
            new OrgUnitService($app->make(OrgUnitRepositoryInterface::class))
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
