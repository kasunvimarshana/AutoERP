<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Providers;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Application\Contracts\ConfigurationCacheKeyFactoryInterface;
use Modules\Configuration\Application\Contracts\ConfigurationCacheInterface;
use Modules\Configuration\Application\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\Application\Contracts\UseCases\ClearConfigurationCacheServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Countries\CreateCountryServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Countries\DeleteCountryServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Countries\GetCountryServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Countries\ListCountriesServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Countries\UpdateCountryServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Currencies\CreateCurrencyServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Currencies\DeleteCurrencyServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Currencies\GetCurrencyServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Currencies\ListCurrenciesServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Currencies\UpdateCurrencyServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\DeleteConfigurationServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\GetConfigurationServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Languages\CreateLanguageServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Languages\DeleteLanguageServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Languages\GetLanguageServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Languages\ListLanguagesServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Languages\UpdateLanguageServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\ListConfigurationsServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\SetConfigurationFromCliServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\SetConfigurationServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Timezones\CreateTimezoneServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Timezones\DeleteTimezoneServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Timezones\GetTimezoneServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Timezones\ListTimezonesServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Timezones\UpdateTimezoneServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\UpdateConfigurationServiceInterface;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Application\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Application\Repositories\CurrencyRepositoryInterface;
use Modules\Configuration\Application\Repositories\LanguageRepositoryInterface;
use Modules\Configuration\Application\Repositories\TimezoneRepositoryInterface;
use Modules\Configuration\Application\Support\ConfigurationCacheKeyFactory;
use Modules\Configuration\Application\Support\ConfigurationRecordMapper;
use Modules\Configuration\Application\UseCases\ClearConfigurationCacheService;
use Modules\Configuration\Application\UseCases\Countries\CreateCountryService;
use Modules\Configuration\Application\UseCases\Countries\DeleteCountryService;
use Modules\Configuration\Application\UseCases\Countries\GetCountryService;
use Modules\Configuration\Application\UseCases\Countries\ListCountriesService;
use Modules\Configuration\Application\UseCases\Countries\UpdateCountryService;
use Modules\Configuration\Application\UseCases\Currencies\CreateCurrencyService;
use Modules\Configuration\Application\UseCases\Currencies\DeleteCurrencyService;
use Modules\Configuration\Application\UseCases\Currencies\GetCurrencyService;
use Modules\Configuration\Application\UseCases\Currencies\ListCurrenciesService;
use Modules\Configuration\Application\UseCases\Currencies\UpdateCurrencyService;
use Modules\Configuration\Application\UseCases\DeleteConfigurationService;
use Modules\Configuration\Application\UseCases\GetConfigurationService;
use Modules\Configuration\Application\UseCases\Languages\CreateLanguageService;
use Modules\Configuration\Application\UseCases\Languages\DeleteLanguageService;
use Modules\Configuration\Application\UseCases\Languages\GetLanguageService;
use Modules\Configuration\Application\UseCases\Languages\ListLanguagesService;
use Modules\Configuration\Application\UseCases\Languages\UpdateLanguageService;
use Modules\Configuration\Application\UseCases\ListConfigurationsService;
use Modules\Configuration\Application\UseCases\SetConfigurationFromCliService;
use Modules\Configuration\Application\UseCases\SetConfigurationService;
use Modules\Configuration\Application\UseCases\Timezones\CreateTimezoneService;
use Modules\Configuration\Application\UseCases\Timezones\DeleteTimezoneService;
use Modules\Configuration\Application\UseCases\Timezones\GetTimezoneService;
use Modules\Configuration\Application\UseCases\Timezones\ListTimezonesService;
use Modules\Configuration\Application\UseCases\Timezones\UpdateTimezoneService;
use Modules\Configuration\Application\UseCases\UpdateConfigurationService;
use Modules\Configuration\Domain\Constants\ConfigurationDefaults;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Configuration\Domain\Services\ConfigurationDomainService;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\ConfigurationModel;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CountryModel;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\LanguageModel;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\TimezoneModel;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentConfigurationRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentCountryRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentCurrencyRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentLanguageRepository;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories\EloquentTimezoneRepository;
use Modules\Configuration\Infrastructure\Services\ConfigurationCacheStore;
use Modules\Configuration\Presentation\Console\Commands\ConfigClearCacheCommand;
use Modules\Configuration\Presentation\Console\Commands\ConfigGetCommand;
use Modules\Configuration\Presentation\Console\Commands\ConfigListCommand;
use Modules\Configuration\Presentation\Console\Commands\ConfigSetCommand;

final class ConfigurationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/configuration.php', 'configuration');

        $this->app->singleton(ConfigurationDomainServiceInterface::class, ConfigurationDomainService::class);

        $this->app->singleton(ConfigurationCacheKeyFactoryInterface::class, ConfigurationCacheKeyFactory::class);
        $this->app->singleton(ConfigurationRecordMapperInterface::class, ConfigurationRecordMapper::class);

        foreach (
            [
                ListConfigurationsServiceInterface::class => ListConfigurationsService::class,
                GetConfigurationServiceInterface::class => GetConfigurationService::class,
                SetConfigurationServiceInterface::class => SetConfigurationService::class,
                UpdateConfigurationServiceInterface::class => UpdateConfigurationService::class,
                DeleteConfigurationServiceInterface::class => DeleteConfigurationService::class,
                ClearConfigurationCacheServiceInterface::class => ClearConfigurationCacheService::class,
                SetConfigurationFromCliServiceInterface::class => SetConfigurationFromCliService::class,

                ListCountriesServiceInterface::class => ListCountriesService::class,
                GetCountryServiceInterface::class => GetCountryService::class,
                CreateCountryServiceInterface::class => CreateCountryService::class,
                UpdateCountryServiceInterface::class => UpdateCountryService::class,
                DeleteCountryServiceInterface::class => DeleteCountryService::class,

                ListCurrenciesServiceInterface::class => ListCurrenciesService::class,
                GetCurrencyServiceInterface::class => GetCurrencyService::class,
                CreateCurrencyServiceInterface::class => CreateCurrencyService::class,
                UpdateCurrencyServiceInterface::class => UpdateCurrencyService::class,
                DeleteCurrencyServiceInterface::class => DeleteCurrencyService::class,

                ListLanguagesServiceInterface::class => ListLanguagesService::class,
                GetLanguageServiceInterface::class => GetLanguageService::class,
                CreateLanguageServiceInterface::class => CreateLanguageService::class,
                UpdateLanguageServiceInterface::class => UpdateLanguageService::class,
                DeleteLanguageServiceInterface::class => DeleteLanguageService::class,

                ListTimezonesServiceInterface::class => ListTimezonesService::class,
                GetTimezoneServiceInterface::class => GetTimezoneService::class,
                CreateTimezoneServiceInterface::class => CreateTimezoneService::class,
                UpdateTimezoneServiceInterface::class => UpdateTimezoneService::class,
                DeleteTimezoneServiceInterface::class => DeleteTimezoneService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(ConfigurationRepositoryInterface::class, function (): ConfigurationRepositoryInterface {
            return new EloquentConfigurationRepository(new ConfigurationModel());
        });

        $this->app->singleton(CountryRepositoryInterface::class, function (): CountryRepositoryInterface {
            return new EloquentCountryRepository(new CountryModel());
        });

        $this->app->singleton(CurrencyRepositoryInterface::class, function (): CurrencyRepositoryInterface {
            return new EloquentCurrencyRepository(new CurrencyModel());
        });

        $this->app->singleton(LanguageRepositoryInterface::class, function (): LanguageRepositoryInterface {
            return new EloquentLanguageRepository(new LanguageModel());
        });

        $this->app->singleton(TimezoneRepositoryInterface::class, function (): TimezoneRepositoryInterface {
            return new EloquentTimezoneRepository(new TimezoneModel());
        });

        $this->app->singleton(ConfigurationCacheInterface::class, function ($app): ConfigurationCacheInterface {
            $store = (string) config('configuration.cache.store', '');
            $prefix = (string) config('configuration.cache.prefix', 'configuration.module');
            $ttlSeconds = (int) config(
                'configuration.cache.ttl_seconds',
                ConfigurationDefaults::DEFAULT_CACHE_TTL_SECONDS,
            );

            /** @var CacheFactory $cacheFactory */
            $cacheFactory = $app->make(CacheFactory::class);
            $cacheRepository = $store === '' ? $cacheFactory->store() : $cacheFactory->store($store);

            return new ConfigurationCacheStore($cacheRepository, $prefix, $ttlSeconds);
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ConfigListCommand::class,
                ConfigGetCommand::class,
                ConfigSetCommand::class,
                ConfigClearCacheCommand::class,
            ]);
        }
    }
}
