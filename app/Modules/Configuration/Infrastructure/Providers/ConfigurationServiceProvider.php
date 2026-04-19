<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Application\Contracts\FindCountriesServiceInterface;
use Modules\Configuration\Application\Contracts\FindCurrenciesServiceInterface;
use Modules\Configuration\Application\Contracts\FindLanguagesServiceInterface;
use Modules\Configuration\Application\Contracts\FindTimezonesServiceInterface;
use Modules\Configuration\Application\Services\FindCountriesService;
use Modules\Configuration\Application\Services\FindCurrenciesService;
use Modules\Configuration\Application\Services\FindLanguagesService;
use Modules\Configuration\Application\Services\FindTimezonesService;
use Modules\Configuration\Domain\RepositoryInterfaces\CountryRepositoryInterface;
use Modules\Configuration\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;
use Modules\Configuration\Domain\RepositoryInterfaces\LanguageRepositoryInterface;
use Modules\Configuration\Domain\RepositoryInterfaces\TimezoneRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentCountryRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentCurrencyRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentLanguageRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentTimezoneRepository;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;

class ConfigurationServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $repositoryBindings = [
            CountryRepositoryInterface::class => EloquentCountryRepository::class,
            CurrencyRepositoryInterface::class => EloquentCurrencyRepository::class,
            LanguageRepositoryInterface::class => EloquentLanguageRepository::class,
            TimezoneRepositoryInterface::class => EloquentTimezoneRepository::class,
        ];

        foreach ($repositoryBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

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
