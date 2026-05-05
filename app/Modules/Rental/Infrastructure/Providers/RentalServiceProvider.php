<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Rental\Application\Contracts\RentalChargeServiceInterface;
use Modules\Rental\Application\Contracts\RentalServiceInterface;
use Modules\Rental\Application\Services\RentalChargeService;
use Modules\Rental\Application\Services\RentalService;
use Modules\Rental\Domain\RepositoryInterfaces\RentalChargeRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalChargeRepository;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalRepository;

class RentalServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(RentalRepositoryInterface::class, EloquentRentalRepository::class);
        $this->app->bind(RentalChargeRepositoryInterface::class, EloquentRentalChargeRepository::class);

        $this->app->bind(RentalServiceInterface::class, RentalService::class);
        $this->app->bind(RentalChargeServiceInterface::class, RentalChargeService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
