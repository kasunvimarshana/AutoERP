<?php

declare(strict_types=1);

namespace Modules\Wms\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Wms\Application\Services\AisleService;
use Modules\Wms\Application\Services\BinService;
use Modules\Wms\Application\Services\CycleCountService;
use Modules\Wms\Application\Services\ZoneService;
use Modules\Wms\Domain\Contracts\AisleRepositoryInterface;
use Modules\Wms\Domain\Contracts\BinRepositoryInterface;
use Modules\Wms\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Wms\Domain\Contracts\ZoneRepositoryInterface;
use Modules\Wms\Infrastructure\Repositories\AisleRepository;
use Modules\Wms\Infrastructure\Repositories\BinRepository;
use Modules\Wms\Infrastructure\Repositories\CycleCountRepository;
use Modules\Wms\Infrastructure\Repositories\ZoneRepository;

class WmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ZoneRepositoryInterface::class, ZoneRepository::class);
        $this->app->bind(AisleRepositoryInterface::class, AisleRepository::class);
        $this->app->bind(BinRepositoryInterface::class, BinRepository::class);
        $this->app->bind(CycleCountRepositoryInterface::class, CycleCountRepository::class);

        $this->app->singleton(ZoneService::class);
        $this->app->singleton(AisleService::class);
        $this->app->singleton(BinService::class);
        $this->app->singleton(CycleCountService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
