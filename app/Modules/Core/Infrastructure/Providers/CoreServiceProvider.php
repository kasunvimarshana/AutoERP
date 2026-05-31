<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Configuration\CoreConfigKey;
use Modules\Core\Application\Contracts\ClockInterface;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\ExceptionParserInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Contracts\PasswordHasherInterface;
use Modules\Core\Application\Contracts\Services\BusinessPartyLinkServiceInterface;
use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\Contracts\UuidGeneratorInterface;
use Modules\Core\Application\Repositories\BusinessPartyLinkRepositoryInterface;
use Modules\Core\Application\Services\BusinessPartyLinkService;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BusinessPartyLinkModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentBusinessPartyLinkRepository;
use Modules\Core\Infrastructure\Services\FileStorageService;
use Modules\Core\Infrastructure\Services\PasswordHasher;
use Modules\Core\Infrastructure\Services\SlugGenerator;
use Modules\Core\Infrastructure\Support\ErrorNormalizer;
use Modules\Core\Infrastructure\Support\ExceptionParser;
use Modules\Core\Infrastructure\Support\LaravelTransactionManager;
use Modules\Core\Infrastructure\Support\RequestCurrentOrganizationUnitContextAccessor;
use Modules\Core\Infrastructure\Support\RequestCurrentTenantContextAccessor;
use Modules\Core\Infrastructure\Support\LaravelUuidGenerator;
use Modules\Core\Infrastructure\Support\RequestCurrentUserContextAccessor;
use Modules\Core\Infrastructure\Support\SystemClock;

final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../Infrastructure/Config/core.php', 'core');

        $this->app->singleton(ClockInterface::class, SystemClock::class);
        $this->app->singleton(UuidGeneratorInterface::class, LaravelUuidGenerator::class);
        $this->app->bind(CurrentUserContextAccessorInterface::class, RequestCurrentUserContextAccessor::class);
        $this->app->bind(CurrentTenantContextAccessorInterface::class, RequestCurrentTenantContextAccessor::class);
        $this->app->bind(
            CurrentOrganizationUnitContextAccessorInterface::class,
            RequestCurrentOrganizationUnitContextAccessor::class,
        );
        $this->app->bind(FileStorageServiceInterface::class, FileStorageService::class);
        $this->app->bind(PasswordHasherInterface::class, PasswordHasher::class);
        $this->app->bind(SlugGeneratorInterface::class, SlugGenerator::class);
        $this->app->singleton(TransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->singleton(ExceptionParserInterface::class, ExceptionParser::class);
        $this->app->singleton(ErrorNormalizerInterface::class, ErrorNormalizer::class);
        $this->app->singleton(BusinessPartyLinkServiceInterface::class, BusinessPartyLinkService::class);
        $this->app->singleton(
            BusinessPartyLinkRepositoryInterface::class,
            static fn (BusinessPartyLinkModel $model): BusinessPartyLinkRepositoryInterface => new EloquentBusinessPartyLinkRepository(
                $model,
            ),
        );

        $this->app->when(FileStorageService::class)
            ->needs('$defaultDisk')
            ->give(static fn (): string => (string) Config::get(CoreConfigKey::FILE_STORAGE_DEFAULT_DISK->value));

        $this->app->when(SlugGenerator::class)
            ->needs('$fallback')
            ->give(static fn (): string => (string) Config::get(CoreConfigKey::SLUG_FALLBACK->value));

        $this->app->when(RequestCurrentUserContextAccessor::class)
            ->needs('$requestAttribute')
            ->give(static fn (): string => (string) Config::get('core.current_user.request_attribute', 'current_user'));

        $this->app->when(RequestCurrentUserContextAccessor::class)
            ->needs('$guardAttribute')
            ->give(
                static fn (): string => (string) Config::get('core.current_user.guard_attribute', 'current_user_guard'),
            );

        $this->app->when(RequestCurrentUserContextAccessor::class)
            ->needs('$providerAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_user.provider_attribute',
                    'current_user_provider',
                ),
            );

        $this->app->when(RequestCurrentUserContextAccessor::class)
            ->needs('$tenantAttribute')
            ->give(
                static fn (): string => (string) Config::get('core.current_user.tenant_attribute', 'current_tenant_id'),
            );

        $this->app->when(RequestCurrentUserContextAccessor::class)
            ->needs('$organizationUnitAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_user.organization_unit_attribute',
                    'current_organization_unit_id',
                ),
            );

        $this->app->when(RequestCurrentUserContextAccessor::class)
            ->needs('$applicationAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_user.application_attribute',
                    'current_application_id',
                ),
            );

        $this->app->when(RequestCurrentUserContextAccessor::class)
            ->needs('$tokenPayloadAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_user.token_payload_attribute',
                    'auth_access_token',
                ),
            );

        $this->app->when(RequestCurrentTenantContextAccessor::class)
            ->needs('$requestAttribute')
            ->give(static fn (): string => (string) Config::get('core.current_tenant.request_attribute', 'current_tenant'));

        $this->app->when(RequestCurrentTenantContextAccessor::class)
            ->needs('$idAttribute')
            ->give(static fn (): string => (string) Config::get('core.current_tenant.id_attribute', 'current_tenant_id'));

        $this->app->when(RequestCurrentTenantContextAccessor::class)
            ->needs('$codeAttribute')
            ->give(static fn (): string => (string) Config::get('core.current_tenant.code_attribute', 'current_tenant_code'));

        $this->app->when(RequestCurrentTenantContextAccessor::class)
            ->needs('$uuidAttribute')
            ->give(static fn (): string => (string) Config::get('core.current_tenant.uuid_attribute', 'current_tenant_uuid'));

        $this->app->when(RequestCurrentTenantContextAccessor::class)
            ->needs('$isolationKeyAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_tenant.isolation_key_attribute',
                    'current_tenant_isolation_key',
                ),
            );

        $this->app->when(RequestCurrentTenantContextAccessor::class)
            ->needs('$domainAttribute')
            ->give(static fn (): string => (string) Config::get('core.current_tenant.domain_attribute', 'current_tenant_domain'));

        $this->app->when(RequestCurrentTenantContextAccessor::class)
            ->needs('$statusAttribute')
            ->give(static fn (): string => (string) Config::get('core.current_tenant.status_attribute', 'current_tenant_status'));

        $this->app->when(RequestCurrentTenantContextAccessor::class)
            ->needs('$activeAttribute')
            ->give(static fn (): string => (string) Config::get('core.current_tenant.active_attribute', 'current_tenant_is_active'));

        $this->app->when(RequestCurrentTenantContextAccessor::class)
            ->needs('$applicationAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_tenant.application_attribute',
                    'current_application_id',
                ),
            );

        $this->app->when(RequestCurrentTenantContextAccessor::class)
            ->needs('$sourceAttribute')
            ->give(static fn (): string => (string) Config::get('core.current_tenant.source_attribute', 'current_tenant_source'));

        $this->app->when(RequestCurrentOrganizationUnitContextAccessor::class)
            ->needs('$requestAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_organization_unit.request_attribute',
                    'current_organization_unit',
                ),
            );

        $this->app->when(RequestCurrentOrganizationUnitContextAccessor::class)
            ->needs('$idAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_organization_unit.id_attribute',
                    'current_organization_unit_id',
                ),
            );

        $this->app->when(RequestCurrentOrganizationUnitContextAccessor::class)
            ->needs('$tenantIdAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_organization_unit.tenant_id_attribute',
                    'current_organization_unit_tenant_id',
                ),
            );

        $this->app->when(RequestCurrentOrganizationUnitContextAccessor::class)
            ->needs('$codeAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_organization_unit.code_attribute',
                    'current_organization_unit_code',
                ),
            );

        $this->app->when(RequestCurrentOrganizationUnitContextAccessor::class)
            ->needs('$pathAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_organization_unit.path_attribute',
                    'current_organization_unit_path',
                ),
            );

        $this->app->when(RequestCurrentOrganizationUnitContextAccessor::class)
            ->needs('$nameAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_organization_unit.name_attribute',
                    'current_organization_unit_name',
                ),
            );

        $this->app->when(RequestCurrentOrganizationUnitContextAccessor::class)
            ->needs('$activeAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_organization_unit.active_attribute',
                    'current_organization_unit_is_active',
                ),
            );

        $this->app->when(RequestCurrentOrganizationUnitContextAccessor::class)
            ->needs('$applicationAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_organization_unit.application_attribute',
                    'current_application_id',
                ),
            );

        $this->app->when(RequestCurrentOrganizationUnitContextAccessor::class)
            ->needs('$sourceAttribute')
            ->give(
                static fn (): string => (string) Config::get(
                    'core.current_organization_unit.source_attribute',
                    'current_organization_unit_source',
                ),
            );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');

        $this->publishes([
            __DIR__ . '/../../Infrastructure/Config/core.php' => config_path('core.php'),
        ], 'core-config');
    }
}
