<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\PartyManagement\Application\Contracts\ManageAssetOwnershipServiceInterface;
use Modules\PartyManagement\Application\Contracts\ManagePartyServiceInterface;
use Modules\PartyManagement\Application\Services\ManageAssetOwnershipService;
use Modules\PartyManagement\Application\Services\ManagePartyService;
use Modules\PartyManagement\Domain\RepositoryInterfaces\AssetOwnershipRepositoryInterface;
use Modules\PartyManagement\Domain\RepositoryInterfaces\PartyRepositoryInterface;
use Modules\PartyManagement\Infrastructure\Persistence\Eloquent\Repositories\EloquentAssetOwnershipRepository;
use Modules\PartyManagement\Infrastructure\Persistence\Eloquent\Repositories\EloquentPartyRepository;

class PartyManagementServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(PartyRepositoryInterface::class, EloquentPartyRepository::class);
        $this->app->bind(AssetOwnershipRepositoryInterface::class, EloquentAssetOwnershipRepository::class);
        $this->app->bind(ManagePartyServiceInterface::class, ManagePartyService::class);
        $this->app->bind(ManageAssetOwnershipServiceInterface::class, ManageAssetOwnershipService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
