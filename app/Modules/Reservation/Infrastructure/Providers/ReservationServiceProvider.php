<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Reservation\Application\Contracts\ReservationServiceInterface;
use Modules\Reservation\Application\Services\ReservationService;
use Modules\Reservation\Domain\RepositoryInterfaces\ReservationRepositoryInterface;
use Modules\Reservation\Infrastructure\Persistence\Eloquent\Repositories\EloquentReservationRepository;

class ReservationServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(ReservationRepositoryInterface::class, EloquentReservationRepository::class);
        $this->app->bind(ReservationServiceInterface::class, ReservationService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
