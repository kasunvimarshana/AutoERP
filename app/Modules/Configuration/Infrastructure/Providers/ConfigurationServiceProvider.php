<?php

namespace Modules\Configuration\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Application\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Application\Repositories\CurrencyRepositoryInterface;
use Modules\Configuration\Application\Repositories\LanguageRepositoryInterface;
use Modules\Configuration\Application\Repositories\TimezoneRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentCountryRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentCurrencyRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentLanguageRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentTimezoneRepository;

class ConfigurationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            CountryRepositoryInterface::class => EloquentCountryRepository::class,
            CurrencyRepositoryInterface::class => EloquentCurrencyRepository::class,
            LanguageRepositoryInterface::class => EloquentLanguageRepository::class,
            TimezoneRepositoryInterface::class => EloquentTimezoneRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
