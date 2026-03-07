<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class    => UserPolicy::class,
        Product::class => ProductPolicy::class,
        Order::class   => OrderPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Passport::tokensExpireIn(
            now()->addDays((int) config('passport.token_expire_days', 15))
        );

        Passport::refreshTokensExpireIn(
            now()->addDays((int) config('passport.refresh_token_expire_days', 30))
        );

        Passport::personalAccessTokensExpireIn(
            now()->addMonths(6)
        );

        // Define Passport scopes
        Passport::tokensCan([
            'read'   => 'Read data',
            'write'  => 'Create and update data',
            'delete' => 'Delete data',
            'admin'  => 'Full administrative access',
        ]);
    }
}
