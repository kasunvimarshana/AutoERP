<?php

namespace Modules\OrganizationUnit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitDocumentRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitSettingGroupRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitSettingRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitTypeRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitDocumentRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitSettingGroupRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitSettingRepository;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitTypeRepository;

class OrganizationUnitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            OrganizationUnitDocumentRepositoryInterface::class => EloquentOrganizationUnitDocumentRepository::class,
            OrganizationUnitRepositoryInterface::class => EloquentOrganizationUnitRepository::class,
            OrganizationUnitSettingGroupRepositoryInterface::class => EloquentOrganizationUnitSettingGroupRepository::class,
            OrganizationUnitSettingRepositoryInterface::class => EloquentOrganizationUnitSettingRepository::class,
            OrganizationUnitTypeRepositoryInterface::class => EloquentOrganizationUnitTypeRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
