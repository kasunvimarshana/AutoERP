<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        // Register Passport routes and configure token lifetime
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // -----------------------------------------------------------------------
        // ABAC Gates
        // -----------------------------------------------------------------------

        // Super-admin bypasses every gate
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        Gate::define('manage-users', function (User $user, ?int $tenantId = null) {
            if ($tenantId) {
                return $user->canManageTenant($tenantId);
            }

            return $user->hasPermission('manage-users');
        });

        Gate::define('manage-tenants', function (User $user) {
            return $user->hasRole('super-admin') || $user->hasPermission('manage-tenants');
        });

        Gate::define('manage-roles', function (User $user) {
            return $user->hasPermission('manage-roles');
        });

        Gate::define('view-any-user', function (User $user) {
            return $user->hasPermission('view-users');
        });

        Gate::define('update-user', function (User $user, User $target) {
            // Users can update themselves; admins can update anyone in their tenant
            return $user->id === $target->id
                || ($user->hasPermission('edit-users') && (string) $user->tenant_id === (string) $target->tenant_id);
        });

        Gate::define('delete-user', function (User $user, User $target) {
            return $user->id !== $target->id
                && $user->hasPermission('delete-users')
                && (string) $user->tenant_id === (string) $target->tenant_id;
        });
    }
}
