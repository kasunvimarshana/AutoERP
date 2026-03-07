<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Inventory\Policies\ProductPolicy;
use App\Domain\Order\Policies\OrderPolicy;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

/**
 * Auth service provider — registers Passport + policies.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Policy mappings.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Order::class   => OrderPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Passport token lifetimes.
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // Define scopes used throughout the API.
        Passport::tokensCan([
            'inventory:read'    => 'Read inventory data',
            'inventory:write'   => 'Create and update inventory',
            'orders:read'       => 'View orders',
            'orders:write'      => 'Create and manage orders',
            'tenants:manage'    => 'Manage tenants (super-admin only)',
            'webhooks:manage'   => 'Manage webhook registrations',
        ]);

        Passport::setDefaultScope([
            'inventory:read',
            'orders:read',
        ]);
    }
}
