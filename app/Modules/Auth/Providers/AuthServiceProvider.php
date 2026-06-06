<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Console\Commands\AuthClientCreateCommand;
use Modules\Auth\Contracts\Providers\AuthProviderRegistryInterface;
use Modules\Auth\Contracts\Providers\IdentityProviderInterface;
use Modules\Auth\Contracts\Providers\SessionProviderInterface;
use Modules\Auth\Contracts\Providers\SsoProviderInterface;
use Modules\Auth\Contracts\Providers\TokenProviderInterface;
use Modules\Auth\Contracts\Providers\VerificationProviderInterface;
use Modules\Auth\Http\Middleware\AuthContextMiddleware;
use Modules\Auth\Http\Middleware\AuthenticateMiddleware;
use Modules\Auth\Http\Middleware\SSOContextMiddleware;
use Modules\Auth\Http\Middleware\TokenValidationMiddleware;
use Modules\Auth\Models\AuthAccessTokenModel;
use Modules\Auth\Models\AuthAuthorizationCodeModel;
use Modules\Auth\Models\AuthClientModel;
use Modules\Auth\Models\AuthIdentityModel;
use Modules\Auth\Models\AuthLoginAttemptModel;
use Modules\Auth\Models\AuthProviderModel;
use Modules\Auth\Models\AuthRefreshTokenModel;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Auth\Models\AuthVerificationChallengeModel;
use Modules\Auth\Policies\AuthClientPolicy;
use Modules\Auth\Repositories\AuthAccessTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthAuthorizationCodeRepositoryInterface;
use Modules\Auth\Repositories\AuthClientRepositoryInterface;
use Modules\Auth\Repositories\AuthIdentityRepositoryInterface;
use Modules\Auth\Repositories\AuthLoginAttemptRepositoryInterface;
use Modules\Auth\Repositories\AuthProviderRepositoryInterface;
use Modules\Auth\Repositories\AuthRefreshTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthSessionRepositoryInterface;
use Modules\Auth\Repositories\AuthVerificationChallengeRepositoryInterface;
use Modules\Auth\Repositories\EloquentAuthAccessTokenRepository;
use Modules\Auth\Repositories\EloquentAuthAuthorizationCodeRepository;
use Modules\Auth\Repositories\EloquentAuthClientRepository;
use Modules\Auth\Repositories\EloquentAuthIdentityRepository;
use Modules\Auth\Repositories\EloquentAuthLoginAttemptRepository;
use Modules\Auth\Repositories\EloquentAuthProviderRepository;
use Modules\Auth\Repositories\EloquentAuthRefreshTokenRepository;
use Modules\Auth\Repositories\EloquentAuthSessionRepository;
use Modules\Auth\Repositories\EloquentAuthVerificationChallengeRepository;
use Modules\Auth\Services\AuthProviderRegistry;
use Modules\Auth\Services\Contracts\AuthDomainServiceInterface;
use Modules\Auth\Services\CurrentUserContextResolver;
use Modules\Auth\Services\DatabaseIdentityProvider;
use Modules\Auth\Services\DatabaseSessionProvider;
use Modules\Auth\Services\DatabaseSsoProvider;
use Modules\Auth\Services\DatabaseTokenProvider;
use Modules\Auth\Services\DatabaseVerificationProvider;
use Modules\Auth\Services\InternalAuthenticationProvider;
use Modules\Auth\Services\Rules\AuthDomainService;
use Modules\Auth\Services\ValidateTokenService;
use Modules\Core\Contracts\CurrentUserContextResolverInterface;
use Modules\User\Models\UserModel;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/auth.php', 'module-auth');

        $this->app->singleton(AuthDomainServiceInterface::class, AuthDomainService::class);

        $this->app->singleton(
            AuthProviderRepositoryInterface::class,
            fn (): AuthProviderRepositoryInterface => new EloquentAuthProviderRepository(new AuthProviderModel),
        );
        $this->app->singleton(
            AuthClientRepositoryInterface::class,
            fn (): AuthClientRepositoryInterface => new EloquentAuthClientRepository(new AuthClientModel),
        );
        $this->app->singleton(
            AuthIdentityRepositoryInterface::class,
            fn (): AuthIdentityRepositoryInterface => new EloquentAuthIdentityRepository(new AuthIdentityModel),
        );
        $this->app->singleton(
            AuthSessionRepositoryInterface::class,
            fn (): AuthSessionRepositoryInterface => new EloquentAuthSessionRepository(new AuthSessionModel),
        );
        $this->app->singleton(
            AuthAccessTokenRepositoryInterface::class,
            fn (): AuthAccessTokenRepositoryInterface => new EloquentAuthAccessTokenRepository(
                new AuthAccessTokenModel,
            ),
        );
        $this->app->singleton(
            AuthRefreshTokenRepositoryInterface::class,
            fn (): AuthRefreshTokenRepositoryInterface => new EloquentAuthRefreshTokenRepository(
                new AuthRefreshTokenModel,
            ),
        );
        $this->app->singleton(
            AuthAuthorizationCodeRepositoryInterface::class,
            fn (): AuthAuthorizationCodeRepositoryInterface => new EloquentAuthAuthorizationCodeRepository(
                new AuthAuthorizationCodeModel,
            ),
        );
        $this->app->singleton(
            AuthVerificationChallengeRepositoryInterface::class,
            fn (): AuthVerificationChallengeRepositoryInterface => new EloquentAuthVerificationChallengeRepository(
                new AuthVerificationChallengeModel,
            ),
        );
        $this->app->singleton(
            AuthLoginAttemptRepositoryInterface::class,
            fn (): AuthLoginAttemptRepositoryInterface => new EloquentAuthLoginAttemptRepository(
                new AuthLoginAttemptModel,
            ),
        );

        $this->app->singleton(InternalAuthenticationProvider::class);
        $this->app->singleton(DatabaseTokenProvider::class);
        $this->app->singleton(DatabaseSessionProvider::class);
        $this->app->singleton(DatabaseSsoProvider::class);
        $this->app->singleton(DatabaseVerificationProvider::class);
        $this->app->singleton(DatabaseIdentityProvider::class);
        $this->app->singleton(TokenProviderInterface::class, DatabaseTokenProvider::class);
        $this->app->singleton(SessionProviderInterface::class, DatabaseSessionProvider::class);
        $this->app->singleton(SsoProviderInterface::class, DatabaseSsoProvider::class);
        $this->app->singleton(VerificationProviderInterface::class, DatabaseVerificationProvider::class);
        $this->app->singleton(IdentityProviderInterface::class, DatabaseIdentityProvider::class);
        $this->app->singleton(AuthProviderRegistryInterface::class, AuthProviderRegistry::class);
        $this->app->singleton(CurrentUserContextResolverInterface::class, CurrentUserContextResolver::class);
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware(
            (string) config('module-auth.middleware.authenticate_alias', 'auth.module.authenticate'),
            AuthenticateMiddleware::class,
        );
        $router->aliasMiddleware(
            (string) config('module-auth.middleware.token_validation_alias', 'auth.module.token'),
            TokenValidationMiddleware::class,
        );
        $router->aliasMiddleware(
            (string) config('module-auth.middleware.context_alias', 'auth.module.context'),
            AuthContextMiddleware::class,
        );
        $router->aliasMiddleware(
            (string) config('module-auth.middleware.sso_context_alias', 'auth.module.sso-context'),
            SSOContextMiddleware::class,
        );

        Auth::viaRequest(
            (string) config('module-auth.token_guard_driver', 'module-auth-token'),
            function (Request $request): ?UserModel {
                $plainAccessToken = $request->bearerToken();
                if (! is_string($plainAccessToken) || trim($plainAccessToken) === '') {
                    return null;
                }

                $tenantInputKey = (string) config('module-auth.token_guard_tenant_input_key', 'tenant_id');
                $tenantId = $request->input($tenantInputKey)
                    ?? $request->headers->get('X-Tenant-ID')
                    ?? $request->headers->get('X-Tenant-Id')
                    ?? $request->headers->get('X-Tenant');
                $validation = $this->app->make(ValidateTokenService::class)->validateToken(
                    $plainAccessToken,
                    is_numeric($tenantId) ? (int) $tenantId : null,
                );

                if ($validation->isFailure()) {
                    return null;
                }

                $payload = $validation->valueOrFail();
                $userId = $payload['user_id'] ?? null;
                if (! is_numeric($userId)) {
                    return null;
                }

                $request->attributes->set('auth_access_token', $payload);

                return UserModel::query()->find((int) $userId);
            },
        );

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Gate::policy(AuthClientModel::class, AuthClientPolicy::class);
        if ($this->app->runningInConsole()) {
            $this->commands([
                AuthClientCreateCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../Config/auth.php' => config_path('module-auth.php'),
            ], 'auth-config');
        }
    }
}
