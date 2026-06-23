<?php

declare(strict_types=1);

namespace Modules\Tenant\Providers;

use Illuminate\Routing\Router;
use LogicException;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Tenant\Console\Commands\TenantActivateCommand;
use Modules\Tenant\Console\Commands\TenantCreateCommand;
use Modules\Tenant\Console\Commands\TenantDeactivateCommand;
use Modules\Tenant\Console\Commands\TenantDomainRevalidateCommand;
use Modules\Tenant\Console\Commands\TenantExpireCommand;
use Modules\Tenant\Console\Commands\TenantPublishEventsCommand;
use Modules\Tenant\Console\Commands\TenantStorageCleanupCommand;
use Modules\Tenant\Console\Commands\TenantSuspendCommand;
use Modules\Tenant\Http\Middleware\RequirePlatformOperatorMiddleware;
use Modules\Tenant\Http\Middleware\RequireTenantFeatureMiddleware;
use Modules\Tenant\Models\TenantDocumentModel;
use Modules\Tenant\Models\TenantDomainModel;
use Modules\Tenant\Models\TenantModel;
use Modules\Tenant\Models\TenantPlanModel;
use Modules\Tenant\Repositories\EloquentTenantDocumentRepository;
use Modules\Tenant\Repositories\EloquentTenantDomainRepository;
use Modules\Tenant\Repositories\EloquentTenantPlanRepository;
use Modules\Tenant\Repositories\EloquentTenantRepository;
use Modules\Tenant\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainOwnershipVerifierInterface;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface;
use Modules\Tenant\Services\CurrentTenantContextResolver;
use Modules\Tenant\Services\Domains\DnsTenantDomainOwnershipVerifier;
use Modules\Tenant\Services\Rules\TenantValueNormalizer;

final class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/tenant.php', 'tenant');
        $this->app->bind(CurrentTenantContextResolverInterface::class, CurrentTenantContextResolver::class);
        $this->app->singleton(TenantValueNormalizerInterface::class, TenantValueNormalizer::class);
        $this->app->singleton(TenantDomainOwnershipVerifierInterface::class, DnsTenantDomainOwnershipVerifier::class);
        $this->app->singleton(TenantRepositoryInterface::class, fn (): TenantRepositoryInterface => new EloquentTenantRepository(new TenantModel));
        $this->app->singleton(TenantPlanRepositoryInterface::class, fn (): TenantPlanRepositoryInterface => new EloquentTenantPlanRepository(new TenantPlanModel));
        $this->app->singleton(TenantDocumentRepositoryInterface::class, fn (): TenantDocumentRepositoryInterface => new EloquentTenantDocumentRepository(new TenantDocumentModel));
        $this->app->singleton(TenantDomainRepositoryInterface::class, fn (): TenantDomainRepositoryInterface => new EloquentTenantDomainRepository(new TenantDomainModel));
    }

    public function boot(): void
    {
        $this->validateDocumentDisk();
        $this->validateResolutionConfiguration();
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware(
            (string) config('tenant.platform.middleware_alias', 'platform.operator'),
            RequirePlatformOperatorMiddleware::class,
        );
        $router->aliasMiddleware(
            (string) config('tenant.entitlements.middleware_alias', 'tenant.feature'),
            RequireTenantFeatureMiddleware::class,
        );
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        if ($this->app->runningInConsole()) {
            $this->commands([TenantCreateCommand::class, TenantActivateCommand::class, TenantSuspendCommand::class, TenantDeactivateCommand::class, TenantDomainRevalidateCommand::class, TenantExpireCommand::class, TenantStorageCleanupCommand::class, TenantPublishEventsCommand::class]);
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
