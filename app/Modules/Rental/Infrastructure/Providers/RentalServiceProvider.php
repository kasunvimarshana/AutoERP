<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Rental\Application\Contracts\CancelRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\ConfirmRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CreateAssetServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalRateCardServiceInterface;
use Modules\Rental\Application\Contracts\FindAssetServiceInterface;
use Modules\Rental\Application\Contracts\FindRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\FindRentalRateCardServiceInterface;
use Modules\Rental\Application\Contracts\UpdateAssetServiceInterface;
use Modules\Rental\Application\Services\CancelRentalBookingService;
use Modules\Rental\Application\Services\ConfirmRentalBookingService;
use Modules\Rental\Application\Services\CreateAssetService;
use Modules\Rental\Application\Services\CreateRentalBookingService;
use Modules\Rental\Application\Services\CreateRentalRateCardService;
use Modules\Rental\Application\Services\FindAssetService;
use Modules\Rental\Application\Services\FindRentalBookingService;
use Modules\Rental\Application\Services\FindRentalRateCardService;
use Modules\Rental\Application\Services\UpdateAssetService;
use Modules\Rental\Domain\RepositoryInterfaces\AssetRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalRateCardRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentAssetRepository;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalBookingRepository;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalRateCardRepository;
use Modules\Service\Domain\Events\ServiceJobCardCompleted;
use Modules\Rental\Infrastructure\Listeners\HandleServiceJobCardCompleted;

class RentalServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        // Repositories
        $this->app->bind(AssetRepositoryInterface::class, EloquentAssetRepository::class);
        $this->app->bind(RentalBookingRepositoryInterface::class, EloquentRentalBookingRepository::class);
        $this->app->bind(RentalRateCardRepositoryInterface::class, EloquentRentalRateCardRepository::class);

        // Services
        $this->app->bind(CreateAssetServiceInterface::class, CreateAssetService::class);
        $this->app->bind(FindAssetServiceInterface::class, FindAssetService::class);
        $this->app->bind(UpdateAssetServiceInterface::class, UpdateAssetService::class);
        $this->app->bind(CreateRentalBookingServiceInterface::class, CreateRentalBookingService::class);
        $this->app->bind(FindRentalBookingServiceInterface::class, FindRentalBookingService::class);
        $this->app->bind(ConfirmRentalBookingServiceInterface::class, ConfirmRentalBookingService::class);
        $this->app->bind(CancelRentalBookingServiceInterface::class, CancelRentalBookingService::class);
        $this->app->bind(CreateRentalRateCardServiceInterface::class, CreateRentalRateCardService::class);
        $this->app->bind(FindRentalRateCardServiceInterface::class, FindRentalRateCardService::class);
    }

    public function boot(): void
    {
        // Real-time status bridge: when a service job completes, release asset rental hold
        Event::listen(ServiceJobCardCompleted::class, HandleServiceJobCardCompleted::class);

        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
