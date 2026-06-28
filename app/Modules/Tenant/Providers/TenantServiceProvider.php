<?php

declare(strict_types=1);

namespace Modules\Tenant\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Core\Contracts\TenantAggregateLockInterface;
use Modules\Core\Contracts\TenantAuthenticationDirectoryInterface;
use Modules\Core\Contracts\TenantDirectoryInterface;
use Modules\Core\Contracts\TenantEntitlementReaderInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TenantPrivateFileServiceInterface;
use Modules\Tenant\Console\Commands\TenantActivateCommand;
use Modules\Tenant\Console\Commands\TenantCreateCommand;
use Modules\Tenant\Console\Commands\TenantDeactivateCommand;
use Modules\Tenant\Console\Commands\TenantDomainRevalidateCommand;
use Modules\Tenant\Console\Commands\TenantExpireCommand;
use Modules\Tenant\Console\Commands\TenantPublishEventsCommand;
use Modules\Tenant\Console\Commands\TenantRetryDeadEventsCommand;
use Modules\Tenant\Console\Commands\TenantStorageCleanupCommand;
use Modules\Tenant\Console\Commands\TenantSuspendCommand;
use Modules\Tenant\Constants\TenantDatabaseStrategy;
use Modules\Tenant\Constants\TenantPermission;
use Modules\Tenant\Http\Middleware\RequireCentralHostMiddleware;
use Modules\Tenant\Http\Middleware\RequirePlatformOperatorMiddleware;
use Modules\Tenant\Http\Middleware\RequireTenantFeatureMiddleware;
use Modules\Tenant\Models\TenantCurrentSubscriptionModel;
use Modules\Tenant\Models\TenantDocumentModel;
use Modules\Tenant\Models\TenantDomainModel;
use Modules\Tenant\Models\TenantModel;
use Modules\Tenant\Models\TenantPlanModel;
use Modules\Tenant\Models\TenantPlanRevisionModel;
use Modules\Tenant\Models\TenantSubscriptionEventModel;
use Modules\Tenant\Models\TenantSubscriptionModel;
use Modules\Tenant\Models\TenantPrimaryDomainModel;
use Modules\Tenant\Repositories\EloquentTenantDocumentRepository;
use Modules\Tenant\Repositories\EloquentTenantDomainRepository;
use Modules\Tenant\Repositories\EloquentTenantPlanRepository;
use Modules\Tenant\Repositories\EloquentTenantPlanRevisionRepository;
use Modules\Tenant\Repositories\EloquentTenantSubscriptionRepository;
use Modules\Tenant\Repositories\EloquentTenantRepository;
use Modules\Tenant\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Repositories\TenantPlanRevisionRepositoryInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantBrandingAssetReaderInterface;
use Modules\Tenant\Services\Contracts\TenantDomainOwnershipVerifierInterface;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface;
use Modules\Configuration\Contracts\ConfigurationTargetPopulationInterface;
use Modules\Configuration\Contracts\ConfigurationTargetValidatorInterface;
use Modules\Tenant\Services\Configuration\TenantConfigurationTargetPopulation;
use Modules\Tenant\Services\Configuration\TenantConfigurationTargetValidator;
use Modules\Tenant\Services\Concurrency\TenantAggregateLock;
use Modules\Tenant\Services\Authentication\TenantAuthenticationDirectory;
use Modules\Tenant\Services\CurrentTenantContextResolver;
use Modules\Tenant\Services\Directory\TenantDirectory;
use Modules\Tenant\Services\Domains\DnsTenantDomainOwnershipVerifier;
use Modules\Tenant\Services\Domains\TenantDomainReadinessPolicy;
use Modules\Tenant\Services\Documents\Scanning\ClamAvTenantDocumentScanner;
use Modules\Tenant\Services\Documents\Scanning\TenantDocumentScannerInterface;
use Modules\Tenant\Services\Documents\Scanning\TrustedLocalTenantDocumentScanner;
use Modules\Tenant\Services\Hosts\PlatformHostPolicy;
use Modules\Tenant\Services\Rules\TenantValueNormalizer;
use Modules\Tenant\Services\Storage\TenantBrandingAssetReader;
use Modules\Tenant\Services\Storage\TenantPrivateFileService;
use Modules\Tenant\Services\Subscriptions\TenantStorageLimitUsageContributor;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionReadinessService;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionPresenter;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/tenant.php', 'tenant');
        $this->app->singleton(ConfigurationTargetValidatorInterface::class, TenantConfigurationTargetValidator::class);
        $this->app->singleton(ConfigurationTargetPopulationInterface::class, TenantConfigurationTargetPopulation::class);
        $this->app->bind(CurrentTenantContextResolverInterface::class, CurrentTenantContextResolver::class);
        $this->app->singleton(TenantValueNormalizerInterface::class, TenantValueNormalizer::class);
        $this->app->singleton(TenantBrandingAssetReaderInterface::class, TenantBrandingAssetReader::class);
        $this->app->singleton(TenantDomainOwnershipVerifierInterface::class, DnsTenantDomainOwnershipVerifier::class);
        $this->app->singleton(TenantDocumentScannerInterface::class, function (): TenantDocumentScannerInterface {
            $driver = strtolower(trim((string) config('tenant.documents.scanner.driver', 'clamav')));

            return match ($driver) {
                'clamav' => new ClamAvTenantDocumentScanner(
                    trim((string) config('tenant.documents.scanner.clamav.host', '127.0.0.1')),
                    (int) config('tenant.documents.scanner.clamav.port', 3310),
                    (float) config('tenant.documents.scanner.clamav.timeout_seconds', 10),
                ),
                'trusted_local' => new TrustedLocalTenantDocumentScanner(),
                default => throw new LogicException(sprintf('Unsupported tenant document scanner [%s].', $driver)),
            };
        });
        $this->app->singleton(PlatformHostPolicy::class);
        $this->app->scoped(TenantAggregateLockInterface::class, TenantAggregateLock::class);
        $this->app->scoped(TenantAuthenticationDirectoryInterface::class, TenantAuthenticationDirectory::class);
        $this->app->scoped(TenantDirectoryInterface::class, TenantDirectory::class);
        $this->app->scoped(TenantEntitlementReaderInterface::class, \Modules\Tenant\Services\TenantEntitlementService::class);
        $this->app->singleton(TenantPrivateFileServiceInterface::class, TenantPrivateFileService::class);
        $this->app->singleton(TenantRepositoryInterface::class, fn ($app): TenantRepositoryInterface => new EloquentTenantRepository(
            new TenantModel,
            $app->make(\Modules\Core\Contracts\ClockInterface::class),
            $app->make(TenantSubscriptionPresenter::class),
            $app->make(\Modules\ReferenceData\Contracts\CurrencyDirectoryInterface::class),
        ));
        $this->app->singleton(TenantPlanRepositoryInterface::class, fn ($app): TenantPlanRepositoryInterface => new EloquentTenantPlanRepository(
            new TenantPlanModel,
            $app->make(\Modules\Core\Contracts\ClockInterface::class),
            $app->make(\Modules\ReferenceData\Contracts\CurrencyDirectoryInterface::class),
        ));
        $this->app->singleton(TenantPlanRevisionRepositoryInterface::class, fn ($app): TenantPlanRevisionRepositoryInterface => new EloquentTenantPlanRevisionRepository(
            new TenantPlanModel,
            new TenantPlanRevisionModel,
            $app->make(\Modules\Core\Contracts\ClockInterface::class),
            $app->make(\Modules\ReferenceData\Contracts\CurrencyDirectoryInterface::class),
        ));
        $this->app->scoped(TenantSubscriptionRepositoryInterface::class, fn ($app): TenantSubscriptionRepositoryInterface => new EloquentTenantSubscriptionRepository(
            new TenantSubscriptionModel,
            new TenantCurrentSubscriptionModel,
            new TenantSubscriptionEventModel,
            $app->make(\Modules\Core\Contracts\ClockInterface::class),
            $app->make(TenantSubscriptionPresenter::class),
            $app->make(\Modules\ReferenceData\Contracts\CurrencyDirectoryInterface::class),
        ));
        $this->app->tag([TenantStorageLimitUsageContributor::class], 'tenant.limit_usage');
        $this->app->scoped(TenantSubscriptionReadinessService::class, fn ($app): TenantSubscriptionReadinessService => new TenantSubscriptionReadinessService(
            $app->make(TenantRepositoryInterface::class),
            $app->make(TenantPlanRevisionRepositoryInterface::class),
            $app->make(TenantSubscriptionRepositoryInterface::class),
            $app->make(\Modules\Tenant\Services\Plans\TenantPlanSchema::class),
            $app->make(TenantExecutionContextInterface::class),
            $app->tagged('tenant.limit_usage'),
        ));
        $this->app->singleton(TenantDocumentRepositoryInterface::class, fn ($app): TenantDocumentRepositoryInterface => new EloquentTenantDocumentRepository(new TenantDocumentModel, $app->make(\Modules\Core\Contracts\ClockInterface::class)));
        $this->app->scoped(TenantDomainRepositoryInterface::class, fn ($app): TenantDomainRepositoryInterface => new EloquentTenantDomainRepository(
            new TenantDomainModel,
            new TenantPrimaryDomainModel,
            $app->make(TenantExecutionContextInterface::class),
            $app->make(\Modules\Core\Contracts\ClockInterface::class),
            $app->make(TenantDomainReadinessPolicy::class),
        ));
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('tenant', TenantPermission::descriptions());
        $this->validateInfrastructureConfiguration();
        $this->validateDocumentDisk();
        $this->validateDocumentScanner();
        $this->validateResolutionConfiguration();
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware(
            (string) config('tenant.platform.host_middleware_alias', 'platform.host'),
            RequireCentralHostMiddleware::class,
        );
        $router->aliasMiddleware(
            (string) config('tenant.platform.operator_middleware_alias', 'platform.operator'),
            RequirePlatformOperatorMiddleware::class,
        );
        $router->aliasMiddleware(
            (string) config('tenant.entitlements.middleware_alias', 'tenant.feature'),
            RequireTenantFeatureMiddleware::class,
        );
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        if ($this->app->runningInConsole()) {
            // Keep command constructors dependency-free so setup and recovery commands
            // can boot before runtime services such as Auth cryptography are configured.
            $this->commands([
                TenantCreateCommand::class,
                TenantActivateCommand::class,
                TenantSuspendCommand::class,
                TenantDeactivateCommand::class,
                TenantDomainRevalidateCommand::class,
                TenantExpireCommand::class,
                TenantStorageCleanupCommand::class,
                TenantPublishEventsCommand::class,
                TenantRetryDeadEventsCommand::class,
            ]);
        }
    }

    private function validateInfrastructureConfiguration(): void
    {
        $strategy = strtolower(trim((string) config('tenant.infrastructure.database_strategy')));
        if (! in_array($strategy, TenantDatabaseStrategy::supported(), true)) {
            throw new LogicException(sprintf(
                'Unsupported tenant database strategy [%s]. Supported strategies: %s.',
                $strategy === '' ? 'empty' : $strategy,
                implode(', ', TenantDatabaseStrategy::supported()),
            ));
        }
    }

    private function validateDocumentDisk(): void
    {
        $disk = trim((string) config('tenant.documents.disk', 'tenant_private'));
        $configuration = config("filesystems.disks.{$disk}");

        if ($disk === '' || ! is_array($configuration)) {
            throw new LogicException('Tenant document storage disk is not configured.');
        }

        if (
            $disk === 'public'
            || ($configuration['visibility'] ?? null) === 'public'
            || (bool) ($configuration['serve'] ?? false)
        ) {
            throw new LogicException('Tenant documents require a non-public, non-served storage disk.');
        }
    }


    private function validateDocumentScanner(): void
    {
        $driver = strtolower(trim((string) config('tenant.documents.scanner.driver', 'clamav')));
        if (! in_array($driver, ['clamav', 'trusted_local'], true)) {
            throw new LogicException(sprintf('Unsupported tenant document scanner [%s].', $driver));
        }

        if ($this->app->environment('production') && $driver !== 'clamav') {
            throw new LogicException('Production tenant document uploads require the ClamAV scanner.');
        }

        if ($driver === 'clamav') {
            $host = trim((string) config('tenant.documents.scanner.clamav.host', ''));
            $port = (int) config('tenant.documents.scanner.clamav.port', 0);
            if ($host === '' || $port < 1 || $port > 65535) {
                throw new LogicException('ClamAV tenant document scanner connection is not configured.');
            }
        }
    }

    private function validateResolutionConfiguration(): void
    {
        $centralHosts = config('tenant.resolution.central_hosts', []);
        if (! is_array($centralHosts)) {
            throw new LogicException('Tenant central hosts must be configured as a list.');
        }

        $normalizedHosts = array_values(array_filter(array_map(
            static fn (mixed $host): string => is_scalar($host)
                ? strtolower(rtrim(trim((string) $host), '.'))
                : '',
            $centralHosts,
        )));

        if ($this->app->environment('production') && $normalizedHosts === []) {
            throw new LogicException('TENANT_CENTRAL_HOSTS must contain at least one trusted platform host in production.');
        }

        foreach (['id', 'code'] as $selectionHeader) {
            if (trim((string) config("tenant.resolution.selection_headers.{$selectionHeader}", '')) === '') {
                throw new LogicException("Tenant selection header [{$selectionHeader}] is not configured.");
            }
        }
    }
}
