<?php

namespace Modules\Tax\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tax\Domain\Contracts\TaxRateRepositoryInterface;
use Modules\Tax\Infrastructure\Repositories\TaxRateRepository;

class TaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaxRateRepositoryInterface::class, TaxRateRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'tax');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
