<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Configuration\Constants\ConfigurationPermission;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Configuration\Contracts\ConfigurationTargetPopulationInterface;
use Modules\Configuration\Contracts\ConfigurationTargetValidatorInterface;
use Modules\Configuration\Contracts\ConfigurationValueRepositoryInterface;
use Modules\Configuration\Contracts\MailConfigurationResolverInterface;
use Modules\Configuration\Contracts\SystemPreferencesResolverInterface;
use Modules\Configuration\Repositories\EloquentConfigurationValueRepository;
use Modules\Configuration\Services\ConfigurationAuthorizationService;
use Modules\Configuration\Services\ConfigurationDefinitionRegistry;
use Modules\Configuration\Services\ConfigurationEntryService;
use Modules\Configuration\Services\ConfigurationGlobalImpactService;
use Modules\Configuration\Services\ConfigurationRevisionService;
use Modules\Configuration\Services\ConfigurationScopeResolver;
use Modules\Configuration\Services\ConfigurationTransferService;
use Modules\Configuration\Services\MailConfigurationResolver;
use Modules\Configuration\Services\ResolveConfiguration;
use Modules\Configuration\Services\SystemPreferencesResolver;
use Modules\Configuration\Services\Targets\TenantBackedConfigurationTargetPopulation;
use Modules\Configuration\Services\Targets\TenantBackedConfigurationTargetValidator;
use Modules\Configuration\Services\TimezoneResolver;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Core\Contracts\TimezoneResolverInterface;

final class ConfigurationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/configuration.php', 'configuration');

        $this->app->singleton(
            ConfigurationDefinitionRegistryInterface::class,
            ConfigurationDefinitionRegistry::class,
        );
        $this->app->singleton(
            ConfigurationValueRepositoryInterface::class,
            EloquentConfigurationValueRepository::class,
        );
        $this->app->singleton(ConfigurationTargetValidatorInterface::class, TenantBackedConfigurationTargetValidator::class);
        $this->app->singleton(ConfigurationTargetPopulationInterface::class, TenantBackedConfigurationTargetPopulation::class);

        $this->app->scoped(ConfigurationScopeResolver::class);
        $this->app->scoped(ConfigurationAuthorizationService::class);
        $this->app->scoped(ConfigurationEntryService::class);
        $this->app->scoped(ConfigurationGlobalImpactService::class);
        $this->app->scoped(ConfigurationTransferService::class);
        $this->app->scoped(ConfigurationRevisionService::class);
        $this->app->scoped(ResolveConfiguration::class);
        $this->app->scoped(
            ConfigurationResolverInterface::class,
            fn ($app): ConfigurationResolverInterface => $app->make(ResolveConfiguration::class),
        );
        $this->app->scoped(
            MailConfigurationResolverInterface::class,
            MailConfigurationResolver::class,
        );
        $this->app->scoped(
            SystemPreferencesResolverInterface::class,
            SystemPreferencesResolver::class,
        );
        $this->app->scoped(
            TimezoneResolverInterface::class,
            TimezoneResolver::class,
        );
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('configuration', ConfigurationPermission::descriptions());

        /** @var array{definitions: array<string, array<string, mixed>>} $configuration */
        $configuration = require __DIR__.'/../Config/configuration.php';
        $this->app->make(ConfigurationDefinitionRegistryInterface::class)
            ->register('Configuration', $configuration['definitions']);

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
