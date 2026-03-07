<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\User;
use App\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model-to-policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Tenant::class => TenantPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // -----------------------------------------------------------------------
        // Passport – token lifetimes & scopes
        // -----------------------------------------------------------------------

        Passport::tokensExpireIn(
            now()->addDays(config('passport.token_expire_days', 7))
        );

        Passport::refreshTokensExpireIn(
            now()->addDays(config('passport.refresh_token_expire_days', 30))
        );

        Passport::personalAccessTokensExpireIn(
            now()->addMonths(6)
        );

        // Define all valid scopes that a token may carry.
        Passport::tokensCan([
            '*'      => 'Full access (admin only)',
            'read'   => 'Read any resource',
            'write'  => 'Create and update resources',
            'delete' => 'Delete resources',
        ]);

        Passport::setDefaultScope(['read']);

        // -----------------------------------------------------------------------
        // Gate abilities – ABAC gates complement the Eloquent policies above
        // -----------------------------------------------------------------------

        /**
         * A user may only manage resources within their own tenant.
         */
        Gate::define('manage-tenant-resource', function (User $user, object|array $resource): bool {
            $resourceTenantId = is_array($resource)
                ? ($resource['tenant_id'] ?? null)
                : ($resource->tenant_id ?? null);

            if ($resourceTenantId === null) {
                return true;
            }

            return (int) $user->tenant_id === (int) $resourceTenantId;
        });

        /**
         * Only admins may perform administrative actions on a tenant.
         */
        Gate::define('admin-tenant', function (User $user, Tenant $tenant): bool {
            return (int) $user->tenant_id === (int) $tenant->id
                && $user->hasRole('admin');
        });

        /**
         * A user may impersonate another user only if they are a super-admin
         * and the target user belongs to the same tenant.
         */
        Gate::define('impersonate', function (User $actor, User $target): bool {
            return $actor->hasRole('super-admin')
                || (
                    $actor->hasRole('admin')
                    && (int) $actor->tenant_id === (int) $target->tenant_id
                );
        });

        // Register the model policies declared in $this->policies.
        $this->registerPolicies();
    }
}
