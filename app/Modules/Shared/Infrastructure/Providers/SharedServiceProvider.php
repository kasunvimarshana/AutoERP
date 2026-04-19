<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Shared\Application\Contracts\FindCountriesServiceInterface;
use Modules\Shared\Application\Contracts\FindCurrenciesServiceInterface;
use Modules\Shared\Application\Contracts\FindLanguagesServiceInterface;
use Modules\Shared\Application\Contracts\FindTimezonesServiceInterface;
use Modules\Shared\Application\Services\FindCountriesService;
use Modules\Shared\Application\Services\FindCurrenciesService;
use Modules\Shared\Application\Services\FindLanguagesService;
use Modules\Shared\Application\Services\FindTimezonesService;
use Modules\Shared\Domain\RepositoryInterfaces\CountryRepositoryInterface;
use Modules\Shared\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;
use Modules\Shared\Domain\RepositoryInterfaces\LanguageRepositoryInterface;
use Modules\Shared\Domain\RepositoryInterfaces\TimezoneRepositoryInterface;
use Modules\Shared\Infrastructure\Persistence\Eloquent\Models\CountryModel;
use Modules\Shared\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Shared\Infrastructure\Persistence\Eloquent\Models\LanguageModel;
use Modules\Shared\Infrastructure\Persistence\Eloquent\Models\TimezoneModel;
use Modules\Shared\Infrastructure\Persistence\Eloquent\Repositories\EloquentCountryRepository;
use Modules\Shared\Infrastructure\Persistence\Eloquent\Repositories\EloquentCurrencyRepository;
use Modules\Shared\Infrastructure\Persistence\Eloquent\Repositories\EloquentLanguageRepository;
use Modules\Shared\Infrastructure\Persistence\Eloquent\Repositories\EloquentTimezoneRepository;

class SharedServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        // Repositories
        $this->app->bind(CountryRepositoryInterface::class, fn ($app) => new EloquentCountryRepository($app->make(CountryModel::class)));
        $this->app->bind(CurrencyRepositoryInterface::class, fn ($app) => new EloquentCurrencyRepository($app->make(CurrencyModel::class)));
        $this->app->bind(LanguageRepositoryInterface::class, fn ($app) => new EloquentLanguageRepository($app->make(LanguageModel::class)));
        $this->app->bind(TimezoneRepositoryInterface::class, fn ($app) => new EloquentTimezoneRepository($app->make(TimezoneModel::class)));

        // Services
        $this->app->bind(FindCountriesServiceInterface::class, FindCountriesService::class);
        $this->app->bind(FindCurrenciesServiceInterface::class, FindCurrenciesService::class);
        $this->app->bind(FindLanguagesServiceInterface::class, FindLanguagesService::class);
        $this->app->bind(FindTimezonesServiceInterface::class, FindTimezonesService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
