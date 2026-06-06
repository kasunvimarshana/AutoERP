<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Providers;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Application\Contracts\ConfigurationCacheInterface;
use Modules\Configuration\Application\Contracts\ConfigurationCacheKeyFactoryInterface;
use Modules\Configuration\Application\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Application\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Application\Repositories\CurrencyRepositoryInterface;
use Modules\Configuration\Application\Repositories\LanguageRepositoryInterface;
use Modules\Configuration\Application\Repositories\TimezoneRepositoryInterface;
use Modules\Configuration\Application\Support\ConfigurationCacheKeyFactory;
use Modules\Configuration\Application\Support\ConfigurationRecordMapper;
use Modules\Configuration\Domain\Constants\ConfigurationDefaults;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Configuration\Domain\Services\ConfigurationDomainService;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\ConfigurationModel;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CountryModel;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\LanguageModel;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\TenantConfigurationModel;
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
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');

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
