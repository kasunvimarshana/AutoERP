<?php

declare(strict_types=1);

namespace Modules\Tenant\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
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
use Modules\Tenant\Services\Contracts\TenantDomainOwnershipVerifierInterface;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface;
use Modules\Tenant\Services\CurrentTenantContextResolver;
use Modules\Tenant\Services\Domains\DnsTenantDomainOwnershipVerifier;
use Modules\Tenant\Services\Hosts\PlatformHostPolicy;
use Modules\Tenant\Services\Rules\TenantValueNormalizer;
use Modules\Tenant\Services\Subscriptions\TenantStorageLimitUsageContributor;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionReadinessService;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/tenant.php', 'tenant');
        $this->app->bind(CurrentTenantContextResolverInterface::class, CurrentTenantContextResolver::class);
        $this->app->singleton(TenantValueNormalizerInterface::class, TenantValueNormalizer::class);
        $this->app->singleton(TenantDomainOwnershipVerifierInterface::class, DnsTenantDomainOwnershipVerifier::class);
        $this->app->singleton(PlatformHostPolicy::class);
        $this->app->singleton(TenantRepositoryInterface::class, fn (): TenantRepositoryInterface => new EloquentTenantRepository(new TenantModel));
        $this->app->singleton(TenantPlanRepositoryInterface::class, fn (): TenantPlanRepositoryInterface => new EloquentTenantPlanRepository(new TenantPlanModel));
        $this->app->singleton(TenantPlanRevisionRepositoryInterface::class, fn (): TenantPlanRevisionRepositoryInterface => new EloquentTenantPlanRevisionRepository(new TenantPlanModel, new TenantPlanRevisionModel));
        $this->app->scoped(TenantSubscriptionRepositoryInterface::class, fn (): TenantSubscriptionRepositoryInterface => new EloquentTenantSubscriptionRepository(new TenantSubscriptionModel, new TenantCurrentSubscriptionModel));
        $this->app->tag([TenantStorageLimitUsageContributor::class], 'tenant.limit_usage');
        $this->app->scoped(TenantSubscriptionReadinessService::class, fn ($app): TenantSubscriptionReadinessService => new TenantSubscriptionReadinessService(
            $app->make(TenantRepositoryInterface::class),
            $app->make(TenantPlanRevisionRepositoryInterface::class),
            $app->make(TenantSubscriptionRepositoryInterface::class),
            $app->make(\Modules\Tenant\Services\Plans\TenantPlanSchema::class),
            $app->make(TenantExecutionContextInterface::class),
            $app->tagged('tenant.limit_usage'),
        ));
        $this->app->singleton(TenantDocumentRepositoryInterface::class, fn (): TenantDocumentRepositoryInterface => new EloquentTenantDocumentRepository(new TenantDocumentModel));
        $this->app->scoped(TenantDomainRepositoryInterface::class, fn ($app): TenantDomainRepositoryInterface => new EloquentTenantDomainRepository(
            new TenantDomainModel,
            new TenantPrimaryDomainModel,
            $app->make(TenantExecutionContextInterface::class),
        ));
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('tenant', TenantPermission::descriptions());

        $this->validateInfrastructureConfiguration();
        $this->validateDocumentDisk();
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
            $this->commands([TenantCreateCommand::class, TenantActivateCommand::class, TenantSuspendCommand::class, TenantDeactivateCommand::class, TenantDomainRevalidateCommand::class, TenantExpireCommand::class, TenantStorageCleanupCommand::class, TenantPublishEventsCommand::class, TenantRetryDeadEventsCommand::class]);
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
