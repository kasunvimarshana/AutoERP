<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Policies\CategoryPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Product::class => ProductPolicy::class,
        Category::class => CategoryPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-products', fn($user) => $user->hasAnyRole(['admin', 'manager']));
        Gate::define('manage-categories', fn($user) => $user->hasAnyRole(['admin', 'manager']));
    }
}
