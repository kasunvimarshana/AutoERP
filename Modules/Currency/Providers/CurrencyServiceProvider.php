<?php

namespace Modules\Currency\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Currency\Domain\Contracts\CurrencyRepositoryInterface;
use Modules\Currency\Domain\Contracts\ExchangeRateRepositoryInterface;
use Modules\Currency\Infrastructure\Repositories\CurrencyRepository;
use Modules\Currency\Infrastructure\Repositories\ExchangeRateRepository;

class CurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CurrencyRepositoryInterface::class, CurrencyRepository::class);
        $this->app->bind(ExchangeRateRepositoryInterface::class, ExchangeRateRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'currency');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
