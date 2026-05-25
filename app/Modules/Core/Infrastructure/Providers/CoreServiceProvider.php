<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Configuration\CoreConfigKey;
use Modules\Core\Application\Contracts\ClockInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Contracts\PasswordHasherInterface;
use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Contracts\UuidGeneratorInterface;
use Modules\Core\Infrastructure\Services\FileStorageService;
use Modules\Core\Infrastructure\Services\PasswordHasher;
use Modules\Core\Infrastructure\Services\SlugGenerator;
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
        $this->app->bind(FileStorageServiceInterface::class, FileStorageService::class);
        $this->app->bind(PasswordHasherInterface::class, PasswordHasher::class);
        $this->app->bind(SlugGeneratorInterface::class, SlugGenerator::class);

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
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../Infrastructure/Config/core.php' => config_path('core.php'),
        ], 'core-config');
    }
}
