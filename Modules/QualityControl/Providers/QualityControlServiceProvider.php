<?php

namespace Modules\QualityControl\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\QualityControl\Domain\Contracts\InspectionRepositoryInterface;
use Modules\QualityControl\Domain\Contracts\QualityAlertRepositoryInterface;
use Modules\QualityControl\Domain\Contracts\QualityPointRepositoryInterface;
use Modules\QualityControl\Infrastructure\Repositories\InspectionRepository;
use Modules\QualityControl\Infrastructure\Repositories\QualityAlertRepository;
use Modules\QualityControl\Infrastructure\Repositories\QualityPointRepository;

class QualityControlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(QualityPointRepositoryInterface::class, QualityPointRepository::class);
        $this->app->bind(InspectionRepositoryInterface::class, InspectionRepository::class);
        $this->app->bind(QualityAlertRepositoryInterface::class, QualityAlertRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'qualitycontrol');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
