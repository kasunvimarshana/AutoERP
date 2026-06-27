<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Configuration\CoreConfigKey;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\ExceptionParserInterface;
use Modules\PrivateObject\Contracts\PrivateObjectStorageInterface;
use Modules\Auth\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\SlugGeneratorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Modules\Core\Services\DecimalMath;
use Modules\Core\Services\SlugGenerator;
use Modules\Core\Support\ErrorNormalizer;
use Modules\Core\Support\ExceptionParser;
use Modules\Core\Support\LaravelTransactionManager;
use Modules\Core\Support\LaravelUuidGenerator;
use Modules\Core\Support\RequestCurrentOrganizationUnitContextAccessor;
use Modules\Core\Support\RequestCurrentTenantContextAccessor;
use Modules\Core\Support\TenantExecutionContext;
use Modules\Core\Support\RequestCurrentUserContextAccessor;
use Modules\Core\Support\SystemClock;

final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/core.php', 'core');

        $this->app->singleton(ClockInterface::class, SystemClock::class);
        $this->app->singleton(DecimalMath::class);
        $this->app->singleton(UuidGeneratorInterface::class, LaravelUuidGenerator::class);
        $this->app->singleton(TransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->singleton(ExceptionParserInterface::class, ExceptionParser::class);
        $this->app->singleton(ErrorNormalizerInterface::class, ErrorNormalizer::class);
        $this->app->singleton(ApiErrorResponseFactory::class);

        $this->app->bind(CurrentUserContextAccessorInterface::class, RequestCurrentUserContextAccessor::class);
        $this->app->bind(CurrentTenantContextAccessorInterface::class, RequestCurrentTenantContextAccessor::class);
        $this->app->scoped(TenantExecutionContextInterface::class, TenantExecutionContext::class);
        $this->app->bind(
            CurrentOrganizationUnitContextAccessorInterface::class,
            RequestCurrentOrganizationUnitContextAccessor::class,
        );
        $this->app->bind(PrivateObjectStorageInterface::class, FileStorageService::class);
        $this->app->bind(SlugGeneratorInterface::class, SlugGenerator::class);


        $this->app->when(SlugGenerator::class)
            ->needs('$fallback')
            ->give(static fn (): string => (string) Config::get(CoreConfigKey::SLUG_FALLBACK->value));

        $this->bindContextRequestAttribute(
            RequestCurrentUserContextAccessor::class,
            'core.current_user.request_attribute',
            'current_user',
        );
        $this->bindContextRequestAttribute(
            RequestCurrentTenantContextAccessor::class,
            'core.current_tenant.request_attribute',
            'current_tenant',
        );
        $this->bindContextRequestAttribute(
            RequestCurrentOrganizationUnitContextAccessor::class,
            'core.current_organization_unit.request_attribute',
            'current_organization_unit',
        );
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes();

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->publishes([
            __DIR__.'/../Config/core.php' => config_path('core.php'),
        ], 'core-config');
    }

    private function bindContextRequestAttribute(string $accessor, string $configKey, string $fallback): void
    {
        $this->app->when($accessor)
            ->needs('$requestAttribute')
            ->give(static fn (): string => (string) Config::get($configKey, $fallback));
    }
}
