<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Application\Contracts\Providers\IdentityProviderInterface;
use Modules\Auth\Application\Contracts\Providers\SessionProviderInterface;
use Modules\Auth\Application\Contracts\Providers\SsoProviderInterface;
use Modules\Auth\Application\Contracts\Providers\TokenProviderInterface;
use Modules\Auth\Application\Contracts\Providers\VerificationProviderInterface;
use Modules\Auth\Application\Contracts\Providers\AuthProviderRegistryInterface;
use Modules\Auth\Application\Contracts\UseCases\AuthorizeClientServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\ExchangeAuthorizationCodeServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\IssueTokenServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\ListSessionsServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\LoginServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\LogoutServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RefreshTokenServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RegisterServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RequestVerificationChallengeServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RevokeSessionServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\ValidateTokenServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\VerifyChallengeServiceInterface;
use Modules\Auth\Application\UseCases\AuthorizeClientService;
use Modules\Auth\Application\Repositories\AuthAccessTokenRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthAuthorizationCodeRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthClientRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthIdentityRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthLoginAttemptRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthProviderRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthRefreshTokenRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthSessionRepositoryInterface;
use Modules\Auth\Application\Repositories\AuthVerificationChallengeRepositoryInterface;
use Modules\Auth\Application\UseCases\AuthWorkflowService;
use Modules\Auth\Application\UseCases\ExchangeAuthorizationCodeService;
use Modules\Auth\Application\UseCases\IssueTokenService;
use Modules\Auth\Application\UseCases\ListSessionsService;
use Modules\Auth\Application\UseCases\LoginService;
use Modules\Auth\Application\UseCases\LogoutService;
use Modules\Auth\Application\UseCases\RefreshTokenService;
use Modules\Auth\Application\UseCases\RegisterService;
use Modules\Auth\Application\UseCases\RequestVerificationChallengeService;
use Modules\Auth\Application\UseCases\RevokeSessionService;
use Modules\Auth\Application\UseCases\ValidateTokenService;
use Modules\Auth\Application\UseCases\VerifyChallengeService;
use Modules\Auth\Domain\Contracts\AuthDomainServiceInterface;
use Modules\Auth\Domain\Services\AuthDomainService;
use Modules\Auth\Infrastructure\Listeners\RecordAuthLifecycleListener;
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
use Modules\Auth\Infrastructure\Services\DatabaseIdentityProvider;
use Modules\Auth\Infrastructure\Services\DatabaseSessionProvider;
use Modules\Auth\Infrastructure\Services\DatabaseSsoProvider;
use Modules\Auth\Infrastructure\Services\DatabaseTokenProvider;
use Modules\Auth\Infrastructure\Services\DatabaseVerificationProvider;
use Modules\Auth\Infrastructure\Services\InternalAuthenticationProvider;
use Modules\Auth\Presentation\Console\Commands\AuthClientCreateCommand;
use Modules\Auth\Presentation\Policies\AuthClientPolicy;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/auth.php', 'module-auth');

        $this->app->singleton(AuthDomainServiceInterface::class, AuthDomainService::class);

        $this->app->singleton(
            AuthProviderRepositoryInterface::class,
            fn (): AuthProviderRepositoryInterface => new EloquentAuthProviderRepository(new AuthProviderModel()),
        );
        $this->app->singleton(
            AuthClientRepositoryInterface::class,
            fn (): AuthClientRepositoryInterface => new EloquentAuthClientRepository(new AuthClientModel()),
        );
        $this->app->singleton(
            AuthIdentityRepositoryInterface::class,
            fn (): AuthIdentityRepositoryInterface => new EloquentAuthIdentityRepository(new AuthIdentityModel()),
        );
        $this->app->singleton(
            AuthSessionRepositoryInterface::class,
            fn (): AuthSessionRepositoryInterface => new EloquentAuthSessionRepository(new AuthSessionModel()),
        );
        $this->app->singleton(
            AuthAccessTokenRepositoryInterface::class,
            fn (): AuthAccessTokenRepositoryInterface => new EloquentAuthAccessTokenRepository(
                new AuthAccessTokenModel(),
            ),
        );
        $this->app->singleton(
            AuthRefreshTokenRepositoryInterface::class,
            fn (): AuthRefreshTokenRepositoryInterface => new EloquentAuthRefreshTokenRepository(
                new AuthRefreshTokenModel(),
            ),
        );
        $this->app->singleton(
            AuthAuthorizationCodeRepositoryInterface::class,
            fn (): AuthAuthorizationCodeRepositoryInterface => new EloquentAuthAuthorizationCodeRepository(
                new AuthAuthorizationCodeModel(),
            ),
        );
        $this->app->singleton(
            AuthVerificationChallengeRepositoryInterface::class,
            fn (): AuthVerificationChallengeRepositoryInterface => new EloquentAuthVerificationChallengeRepository(
                new AuthVerificationChallengeModel(),
            ),
        );
        $this->app->singleton(
            AuthLoginAttemptRepositoryInterface::class,
            fn (): AuthLoginAttemptRepositoryInterface => new EloquentAuthLoginAttemptRepository(
                new AuthLoginAttemptModel(),
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

        $this->app->singleton(AuthWorkflowService::class);

        foreach (
            [
                LoginServiceInterface::class => LoginService::class,
                LogoutServiceInterface::class => LogoutService::class,
                RegisterServiceInterface::class => RegisterService::class,
                IssueTokenServiceInterface::class => IssueTokenService::class,
                RefreshTokenServiceInterface::class => RefreshTokenService::class,
                RevokeSessionServiceInterface::class => RevokeSessionService::class,
                ListSessionsServiceInterface::class => ListSessionsService::class,
                ValidateTokenServiceInterface::class => ValidateTokenService::class,
                RequestVerificationChallengeServiceInterface::class => RequestVerificationChallengeService::class,
                VerifyChallengeServiceInterface::class => VerifyChallengeService::class,
                AuthorizeClientServiceInterface::class => AuthorizeClientService::class,
                ExchangeAuthorizationCodeServiceInterface::class => ExchangeAuthorizationCodeService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }
    }

    public function boot(): void
    {
        Auth::viaRequest(
            (string) config('module-auth.token_guard_driver', 'module-auth-token'),
            function (Request $request): ?UserModel {
                $plainAccessToken = $request->bearerToken();
                if (! is_string($plainAccessToken) || trim($plainAccessToken) === '') {
                    return null;
                }

                $tenantInputKey = (string) config('module-auth.token_guard_tenant_input_key', 'tenant_id');
                $tenantId = $request->input($tenantInputKey);
                $validation = $this->app->make(ValidateTokenServiceInterface::class)->validateToken(
                    $plainAccessToken,
                    $tenantId !== null ? (int) $tenantId : null,
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

        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');

        Gate::policy(AuthClientModel::class, AuthClientPolicy::class);
        Event::listen('auth.lifecycle', RecordAuthLifecycleListener::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                AuthClientCreateCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../Config/auth.php' => config_path('module-auth.php'),
            ], 'auth-config');
        }
    }
}
