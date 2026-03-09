<?php

namespace App\Providers;

use App\Domain\Models\Tenant;
use App\Domain\Models\User;
use Carbon\CarbonInterval;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Policy mappings for the application.
     */
    protected $policies = [
        User::class   => \App\Policies\UserPolicy::class,
        Tenant::class => \App\Policies\TenantPolicy::class,
    ];

    /**
     * Register authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Register Passport routes
        Passport::routes(function ($router) {
            $router->forAccessTokens();
            $router->forTransientTokens();
            $router->forPersonalAccessTokens();
        });

        // Token lifetimes
        $tokensExpiry   = config('passport.tokens_expire_in');
        $refreshExpiry  = config('passport.refresh_tokens_expire_in');
        $personalExpiry = config('passport.personal_access_tokens_expire_in');

        Passport::tokensExpireIn(
            $tokensExpiry instanceof \DateInterval
                ? $tokensExpiry
                : CarbonInterval::days((int) $tokensExpiry)
        );

        Passport::refreshTokensExpireIn(
            $refreshExpiry instanceof \DateInterval
                ? $refreshExpiry
                : CarbonInterval::days((int) $refreshExpiry)
        );

        Passport::personalAccessTokensExpireIn(
            $personalExpiry instanceof \DateInterval
                ? $personalExpiry
                : CarbonInterval::months((int) $personalExpiry)
        );

        // Enable token hashing (optional but recommended in production)
        // Passport::hashClientSecrets();

        // Passport uses the 'api' guard by default
        Passport::useTokenModel(\Laravel\Passport\Token::class);
    }
}
