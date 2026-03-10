<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Infrastructure\MultiTenant\TenantManager;
use App\Infrastructure\MultiTenant\TenantRepository;
use App\Infrastructure\Persistence\Models\Tenant;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider
 *
 * Binds domain interfaces to concrete infrastructure implementations.
 * All bindings are explicit and DI-resolvable — no hardcoded instantiation.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Domain → Infrastructure bindings ──────────────────────────────────

        $this->app->bind(UserRepositoryInterface::class, function ($app) {
            return new UserRepository($app->make(User::class));
        });

        // TenantRepository (concrete class, no interface needed)
        $this->app->singleton(TenantRepository::class, function ($app) {
            return new TenantRepository($app->make(Tenant::class));
        });

        // TenantManager — singleton so tenant context is shared per request
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager($app->make(TenantRepository::class));
        });
    }

    public function boot(): void
    {
        //
    }
}
