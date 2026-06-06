<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Providers;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Application\Contracts\Providers\AuthProviderRegistryInterface;
use Modules\Auth\Application\Contracts\Providers\IdentityProviderInterface;
use Modules\Auth\Application\Contracts\Providers\SessionProviderInterface;
use Modules\Auth\Application\Contracts\Providers\SsoProviderInterface;
use Modules\Auth\Application\Contracts\Providers\TokenProviderInterface;
use Modules\Auth\Application\Contracts\Providers\VerificationProviderInterface;
use Modules\Auth\Application\Repositories\AuthAccessTokenRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthAuthorizationCodeRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthClientRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthIdentityRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthLoginAttemptRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthProviderRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthRefreshTokenRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthSessionRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthVerificationChallengeRepositoryInterface;
use Modules\Auth\Application\UseCases\ValidateTokenService;
use Modules\Auth\Domain\Contracts\AuthDomainServiceInterface;
use Modules\Auth\Domain\Services\AuthDomainService;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthAccessTokenModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthAuthorizationCodeModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthClientModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthIdentityModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthLoginAttemptModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthProviderModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthRefreshTokenModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthSessionModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\AuthVerificationChallengeModel;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuthAccessTokenRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuthAuthorizationCodeRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuthClientRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuthIdentityRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuthLoginAttemptRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuthProviderRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuthRefreshTokenRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuthSessionRepository;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuthVerificationChallengeRepository;
use Modules\Auth\Infrastructure\Services\AuthProviderRegistry;
use Modules\Auth\Infrastructure\Services\CurrentUserContextResolver;
use Modules\Auth\Infrastructure\Services\DatabaseIdentityProvider;
use Modules\Auth\Infrastructure\Services\DatabaseSessionProvider;
use Modules\Auth\Infrastructure\Services\DatabaseSsoProvider;
use Modules\Auth\Infrastructure\Services\DatabaseTokenProvider;
use Modules\Auth\Infrastructure\Services\DatabaseVerificationProvider;
use Modules\Auth\Infrastructure\Services\InternalAuthenticationProvider;
use Modules\Auth\Presentation\Console\Commands\AuthClientCreateCommand;
use Modules\Auth\Presentation\Http\Middleware\AuthContextMiddleware;
use Modules\Auth\Presentation\Http\Middleware\AuthenticateMiddleware;
use Modules\Auth\Presentation\Http\Middleware\SSOContextMiddleware;
use Modules\Auth\Presentation\Http\Middleware\TokenValidationMiddleware;
use Modules\Auth\Presentation\Policies\AuthClientPolicy;
use Modules\Core\Application\Contracts\CurrentUserContextResolverInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

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

        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');

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
