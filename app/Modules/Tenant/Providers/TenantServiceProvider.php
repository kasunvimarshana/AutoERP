<?php

declare(strict_types=1);

namespace Modules\Tenant\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Tenant\Console\Commands\TenantActivateCommand;
use Modules\Tenant\Console\Commands\TenantCreateCommand;
use Modules\Tenant\Console\Commands\TenantDeactivateCommand;
use Modules\Tenant\Console\Commands\TenantSuspendCommand;
use Modules\Tenant\Http\Middleware\EnsureCentralHostMiddleware;
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
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;
use Modules\Tenant\Services\CurrentTenantContextResolver;
use Modules\Tenant\Services\Domains\DnsTenantDomainOwnershipVerifier;
use Modules\Tenant\Services\Rules\TenantDomainService;

final class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/tenant.php', 'tenant');
        $this->app->bind(CurrentTenantContextResolverInterface::class, CurrentTenantContextResolver::class);
        $this->app->singleton(TenantDomainServiceInterface::class, TenantDomainService::class);
        $this->app->singleton(TenantDomainOwnershipVerifierInterface::class, DnsTenantDomainOwnershipVerifier::class);
        $this->app->singleton(TenantRepositoryInterface::class, fn (): TenantRepositoryInterface => new EloquentTenantRepository(new TenantModel));
        $this->app->singleton(TenantPlanRepositoryInterface::class, fn (): TenantPlanRepositoryInterface => new EloquentTenantPlanRepository(new TenantPlanModel));
        $this->app->singleton(TenantDocumentRepositoryInterface::class, fn (): TenantDocumentRepositoryInterface => new EloquentTenantDocumentRepository(new TenantDocumentModel));
        $this->app->singleton(TenantDomainRepositoryInterface::class, fn (): TenantDomainRepositoryInterface => new EloquentTenantDomainRepository(new TenantDomainModel));
    }

    public function boot(): void
    {
        $this->app->make(Router::class)->aliasMiddleware(
            (string) config('tenant.platform.central_host_middleware_alias', 'platform.central-host'),
            EnsureCentralHostMiddleware::class,
        );

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        if ($this->app->runningInConsole()) {
            $this->commands([TenantCreateCommand::class, TenantActivateCommand::class, TenantSuspendCommand::class, TenantDeactivateCommand::class]);
        }
    }
}
