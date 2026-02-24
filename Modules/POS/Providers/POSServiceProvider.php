<?php

namespace Modules\POS\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\POS\Application\Listeners\HandlePosOrderPlacedLoyaltyListener;
use Modules\POS\Domain\Contracts\LoyaltyProgramRepositoryInterface;
use Modules\POS\Domain\Contracts\PosDiscountRepositoryInterface;
use Modules\POS\Domain\Contracts\PosOrderPaymentRepositoryInterface;
use Modules\POS\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\POS\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\POS\Domain\Contracts\PosTerminalRepositoryInterface;
use Modules\POS\Domain\Events\PosOrderPlaced;
use Modules\POS\Infrastructure\Repositories\LoyaltyProgramRepository;
use Modules\POS\Infrastructure\Repositories\PosDiscountRepository;
use Modules\POS\Infrastructure\Repositories\PosOrderPaymentRepository;
use Modules\POS\Infrastructure\Repositories\PosOrderRepository;
use Modules\POS\Infrastructure\Repositories\PosSessionRepository;
use Modules\POS\Infrastructure\Repositories\PosTerminalRepository;

class POSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PosTerminalRepositoryInterface::class, PosTerminalRepository::class);
        $this->app->bind(PosSessionRepositoryInterface::class, PosSessionRepository::class);
        $this->app->bind(PosOrderRepositoryInterface::class, PosOrderRepository::class);
        $this->app->bind(LoyaltyProgramRepositoryInterface::class, LoyaltyProgramRepository::class);
        $this->app->bind(PosDiscountRepositoryInterface::class, PosDiscountRepository::class);
        $this->app->bind(PosOrderPaymentRepositoryInterface::class, PosOrderPaymentRepository::class);
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'pos');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        Event::listen(PosOrderPlaced::class, HandlePosOrderPlacedLoyaltyListener::class);
    }
}
