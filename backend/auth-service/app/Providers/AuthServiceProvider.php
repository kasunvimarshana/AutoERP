<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Tenant::class => TenantPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // RBAC Gates
        \Illuminate\Support\Facades\Gate::define('manage-users', function ($user) {
            return $user->hasRole('admin');
        });

        \Illuminate\Support\Facades\Gate::define('manage-tenants', function ($user) {
            return $user->hasRole('admin');
        });

        // ABAC Gates
        \Illuminate\Support\Facades\Gate::define('access-region', function ($user, $resource) {
            return ($user->attributes['region'] ?? null) === ($resource->region ?? null)
                || $user->hasRole('admin');
        });
    }
}
