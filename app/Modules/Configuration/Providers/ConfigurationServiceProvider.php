<?php

declare(strict_types=1);

namespace Modules\Configuration\Providers;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Console\Commands\ConfigClearCacheCommand;
use Modules\Configuration\Console\Commands\ConfigGetCommand;
use Modules\Configuration\Console\Commands\ConfigListCommand;
use Modules\Configuration\Console\Commands\ConfigSetCommand;
use Modules\Configuration\Constants\ConfigurationDefaults;
use Modules\Configuration\Contracts\ConfigurationCacheInterface;
use Modules\Configuration\Contracts\ConfigurationCacheKeyFactoryInterface;
use Modules\Configuration\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\Models\ConfigurationModel;
use Modules\Configuration\Models\CountryModel;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Configuration\Models\LanguageModel;
use Modules\Configuration\Models\TenantConfigurationModel;
use Modules\Configuration\Models\TimezoneModel;
use Modules\Configuration\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Repositories\CurrencyRepositoryInterface;
use Modules\Configuration\Repositories\EloquentConfigurationRepository;
use Modules\Configuration\Repositories\EloquentCountryRepository;
use Modules\Configuration\Repositories\EloquentCurrencyRepository;
use Modules\Configuration\Repositories\EloquentLanguageRepository;
use Modules\Configuration\Repositories\EloquentTimezoneRepository;
use Modules\Configuration\Repositories\LanguageRepositoryInterface;
use Modules\Configuration\Repositories\TimezoneRepositoryInterface;
use Modules\Configuration\Services\ConfigurationCacheStore;
use Modules\Configuration\Services\Contracts\ConfigurationDomainServiceInterface;
use Modules\Configuration\Services\Rules\ConfigurationDomainService;
use Modules\Configuration\Support\ConfigurationCacheKeyFactory;
use Modules\Configuration\Support\ConfigurationRecordMapper;

final class ConfigurationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/configuration.php', 'configuration');

        $this->app->singleton(ConfigurationDomainServiceInterface::class, ConfigurationDomainService::class);

        $this->app->singleton(ConfigurationCacheKeyFactoryInterface::class, ConfigurationCacheKeyFactory::class);
        $this->app->singleton(ConfigurationRecordMapperInterface::class, ConfigurationRecordMapper::class);
        $this->app->singleton(ConfigurationRepositoryInterface::class, function (): ConfigurationRepositoryInterface {
            return new EloquentConfigurationRepository(
                new ConfigurationModel,
                new TenantConfigurationModel,
            );
        });

        $this->app->singleton(CountryRepositoryInterface::class, function (): CountryRepositoryInterface {
            return new EloquentCountryRepository(new CountryModel);
        });

        $this->app->singleton(CurrencyRepositoryInterface::class, function (): CurrencyRepositoryInterface {
            return new EloquentCurrencyRepository(new CurrencyModel);
        });

        $this->app->singleton(LanguageRepositoryInterface::class, function (): LanguageRepositoryInterface {
            return new EloquentLanguageRepository(new LanguageModel);
        });

        $this->app->singleton(TimezoneRepositoryInterface::class, function (): TimezoneRepositoryInterface {
            return new EloquentTimezoneRepository(new TimezoneModel);
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
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

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
