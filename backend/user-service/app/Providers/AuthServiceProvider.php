<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // RBAC Gates
        Gate::define('manage-users', fn(User $user) => $user->hasRole('admin'));
        Gate::define('manage-roles', fn(User $user) => $user->hasRole('admin'));
        Gate::define('manage-attributes', fn(User $user) => $user->hasAnyRole(['admin', 'manager']));

        // ABAC Gates
        Gate::define('access-same-department', fn(User $user, User $target) =>
            ($user->attributes['department'] ?? null) === ($target->attributes['department'] ?? null)
            || $user->hasRole('admin')
        );
        Gate::define('access-same-region', fn(User $user, User $target) =>
            ($user->attributes['region'] ?? null) === ($target->attributes['region'] ?? null)
            || $user->hasRole('admin')
        );
    }
}
