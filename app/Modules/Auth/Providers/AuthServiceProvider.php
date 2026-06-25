<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Console\Commands\AuthClientCreateCommand;
use Modules\Auth\Constants\AuthTokenScope;
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
use Modules\Auth\Http\Middleware\RequireRecentPlatformAuthenticationMiddleware;
use Modules\Auth\Listeners\RevokeTenantAccessOnStatusChange;
use Modules\Auth\Models\AuthAccessTokenModel;
use Modules\Auth\Models\AuthAuthorizationCodeModel;
use Modules\Auth\Models\AuthClientModel;
use Modules\Auth\Models\AuthIdentityModel;
use Modules\Auth\Models\AuthLoginAttemptModel;
use Modules\Auth\Models\AuthProviderModel;
use Modules\Auth\Models\AuthPlatformAccessTokenModel;
use Modules\Auth\Models\AuthPlatformRefreshTokenModel;
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
use Modules\Auth\Repositories\AuthPlatformAccessTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthPlatformRefreshTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthRefreshTokenRepositoryInterface;
use Modules\Auth\Repositories\AuthSessionRepositoryInterface;
use Modules\Auth\Repositories\AuthVerificationChallengeRepositoryInterface;
use Modules\Auth\Repositories\EloquentAuthAccessTokenRepository;
use Modules\Auth\Repositories\EloquentAuthAuthorizationCodeRepository;
use Modules\Auth\Repositories\EloquentAuthClientRepository;
use Modules\Auth\Repositories\EloquentAuthIdentityRepository;
use Modules\Auth\Repositories\EloquentAuthLoginAttemptRepository;
use Modules\Auth\Repositories\EloquentAuthProviderRepository;
use Modules\Auth\Repositories\EloquentAuthPlatformAccessTokenRepository;
use Modules\Auth\Repositories\EloquentAuthPlatformRefreshTokenRepository;
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
use Modules\Auth\Services\Mfa\PlatformMfaService;
use Modules\Auth\Services\Mfa\TotpService;
use Modules\Auth\Services\Rules\AuthDomainService;
use Modules\Auth\Services\ValidateTokenService;
use Modules\Core\Contracts\CurrentUserContextResolverInterface;
use Modules\Core\Contracts\OrganizationUnitAuthScopeRevokerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Events\TenantStatusChanged;
use Modules\User\Models\UserModel;
use Modules\Auth\Models\AuthRegistrationInvitationModel;
use Modules\Auth\Services\Provisioning\TenantAuthenticationProvisioner;
use Modules\Auth\Services\Registration\RegistrationInvitationService;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;
use Modules\User\Contracts\PlatformOperatorSessionRevokerInterface;
use Modules\Auth\Services\PlatformSessionService;

use Modules\Auth\Services\OrganizationUnit\AuthOrganizationUnitLifecycleBlocker;
use Modules\Auth\Services\OrganizationUnit\RevokeOrganizationUnitAuthScopeService;
final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/auth.php', 'module-auth');
        $this->app->tag([AuthOrganizationUnitLifecycleBlocker::class], 'organization-unit.lifecycle_blocker');
        $this->app->singleton(OrganizationUnitAuthScopeRevokerInterface::class, RevokeOrganizationUnitAuthScopeService::class);

        $this->app->singleton(AuthDomainServiceInterface::class, AuthDomainService::class);
        $this->app->singleton(RegistrationInvitationService::class);
        $this->app->singleton(TenantAuthenticationProvisionerInterface::class, TenantAuthenticationProvisioner::class);
        $this->app->singleton(PlatformOperatorSessionRevokerInterface::class, PlatformSessionService::class);

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
            AuthPlatformAccessTokenRepositoryInterface::class,
            fn (): AuthPlatformAccessTokenRepositoryInterface => new EloquentAuthPlatformAccessTokenRepository(
                new AuthPlatformAccessTokenModel,
            ),
        );
        $this->app->singleton(
            AuthPlatformRefreshTokenRepositoryInterface::class,
            fn (): AuthPlatformRefreshTokenRepositoryInterface => new EloquentAuthPlatformRefreshTokenRepository(
                new AuthPlatformRefreshTokenModel,
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
        $this->app->singleton(TotpService::class);
        $this->app->scoped(PlatformMfaService::class);
        $this->app->singleton(AuthProviderRegistryInterface::class, AuthProviderRegistry::class);
        $this->app->singleton(CurrentUserContextResolverInterface::class, CurrentUserContextResolver::class);
    }

    public function boot(): void
    {
        $this->app->make(ConfigurationDefinitionRegistryInterface::class)
            ->register('Auth', require __DIR__.'/../Config/configuration-definitions.php');

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
        $router->aliasMiddleware(
            (string) config('module-auth.platform_mfa.middleware_alias', 'platform.step-up'),
            RequireRecentPlatformAuthenticationMiddleware::class,
        );

        Auth::viaRequest(
            (string) config('module-auth.token_guard_driver', 'module-auth-token'),
            function (Request $request): ?UserModel {
                $payload = $this->validatedBearerPayload($request);
                $userId = $payload['user_id'] ?? null;
                $tenantId = $payload['tenant_id'] ?? null;
                if (
                    ($payload['token_scope'] ?? null) !== AuthTokenScope::TENANT
                    || ! is_numeric($userId)
                    || ! is_numeric($tenantId)
                ) {
                    return null;
                }

                $user = $this->app->make(TenantExecutionContextInterface::class)
                    ->runForTenant((int) $tenantId, static fn (): ?UserModel => UserModel::query()
                        ->whereKey((int) $userId)
                        ->where('status', 'active')
                        ->where('is_platform_operator', false)
                        ->first());
                if (! $user instanceof UserModel) {
                    return null;
                }

                $request->attributes->set('auth_access_token', $payload);

                return $user;
            },
        );

        Auth::viaRequest(
            (string) config('module-auth.platform_token_guard_driver', 'module-platform-token'),
            function (Request $request): ?UserModel {
                $payload = $this->validatedBearerPayload($request);
                $userId = $payload['user_id'] ?? null;
                if (
                    ($payload['token_scope'] ?? null) !== AuthTokenScope::PLATFORM
                    || ! is_numeric($userId)
                    || ($payload['tenant_id'] ?? null) !== null
                ) {
                    return null;
                }

                $user = $this->app->make(TenantExecutionContextInterface::class)
                    ->runAsControlPlane(fn (): ?UserModel => UserModel::query()
                        ->whereKey((int) $userId)
                        ->whereNull('tenant_id')
                        ->where('status', 'active')
                        ->where('is_platform_operator', true)
                        ->whereNotNull('platform_login_email')
                        ->first());
                if (! $user instanceof UserModel) {
                    return null;
                }

                $request->attributes->set('auth_access_token', $payload);

                return $user;
            },
        );

        Event::listen(TenantStatusChanged::class, RevokeTenantAccessOnStatusChange::class);

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

    /** @return array<string, mixed> */
    private function validatedBearerPayload(Request $request): array
    {
        $plainAccessToken = $request->bearerToken();
        if (! is_string($plainAccessToken) || trim($plainAccessToken) === '') {
            return [];
        }

        $validation = $this->app->make(ValidateTokenService::class)
            ->validateToken($plainAccessToken);

        return $validation->isSuccess() ? $validation->valueOrFail() : [];
    }
}
