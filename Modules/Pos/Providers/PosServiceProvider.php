<?php

declare(strict_types=1);

namespace Modules\Pos\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Pos\Application\Services\PosOrderService;
use Modules\Pos\Application\Services\PosSessionService;
use Modules\Pos\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\Pos\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\Pos\Infrastructure\Repositories\PosOrderRepository;
use Modules\Pos\Infrastructure\Repositories\PosSessionRepository;

class PosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PosSessionRepositoryInterface::class, PosSessionRepository::class);
        $this->app->bind(PosOrderRepositoryInterface::class, PosOrderRepository::class);
        $this->app->singleton(PosSessionService::class);
        $this->app->singleton(PosOrderService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
