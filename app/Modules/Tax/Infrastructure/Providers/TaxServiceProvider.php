<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tax\Application\Contracts\CalculateTaxServiceInterface;
use Modules\Tax\Application\Contracts\TaxGroupRateServiceInterface;
use Modules\Tax\Application\Contracts\TaxGroupServiceInterface;
use Modules\Tax\Application\Services\CalculateTaxService;
use Modules\Tax\Application\Services\TaxGroupRateService;
use Modules\Tax\Application\Services\TaxGroupService;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRateRepositoryInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxGroupRateRepository;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxGroupRepository;

class TaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaxGroupRepositoryInterface::class, EloquentTaxGroupRepository::class);
        $this->app->bind(TaxGroupRateRepositoryInterface::class, EloquentTaxGroupRateRepository::class);
        $this->app->bind(TaxGroupServiceInterface::class, TaxGroupService::class);
        $this->app->bind(TaxGroupRateServiceInterface::class, TaxGroupRateService::class);
        $this->app->bind(CalculateTaxServiceInterface::class, CalculateTaxService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
