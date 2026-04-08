<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Currency\Application\Contracts\ConvertAmountServiceInterface;
use Modules\Currency\Application\Contracts\CurrencyServiceInterface;
use Modules\Currency\Application\Contracts\ExchangeRateServiceInterface;
use Modules\Currency\Application\Services\ConvertAmountService;
use Modules\Currency\Application\Services\CurrencyService;
use Modules\Currency\Application\Services\ExchangeRateService;
use Modules\Currency\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;
use Modules\Currency\Domain\RepositoryInterfaces\ExchangeRateRepositoryInterface;
use Modules\Currency\Infrastructure\Persistence\Eloquent\Repositories\EloquentCurrencyRepository;
use Modules\Currency\Infrastructure\Persistence\Eloquent\Repositories\EloquentExchangeRateRepository;

class CurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CurrencyRepositoryInterface::class, EloquentCurrencyRepository::class);
        $this->app->bind(ExchangeRateRepositoryInterface::class, EloquentExchangeRateRepository::class);
        $this->app->bind(CurrencyServiceInterface::class, CurrencyService::class);
        $this->app->bind(ExchangeRateServiceInterface::class, ExchangeRateService::class);
        $this->app->bind(ConvertAmountServiceInterface::class, ConvertAmountService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
