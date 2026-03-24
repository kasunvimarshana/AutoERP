<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;
use Modules\Auth\Application\Contracts\AuthenticationServiceInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Auth\Application\Contracts\ForgotPasswordServiceInterface;
use Modules\Auth\Application\Contracts\LoginServiceInterface;
use Modules\Auth\Application\Contracts\LogoutServiceInterface;
use Modules\Auth\Application\Contracts\RefreshTokenServiceInterface;
use Modules\Auth\Application\Contracts\RegisterUserServiceInterface;
use Modules\Auth\Application\Contracts\ResetPasswordServiceInterface;
use Modules\Auth\Application\Contracts\SsoServiceInterface;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Application\Services\AbacAuthorizationStrategy;
use Modules\Auth\Application\Services\AuthenticationService;
use Modules\Auth\Application\Services\AuthorizationService;
use Modules\Auth\Application\Services\ForgotPasswordService;
use Modules\Auth\Application\Services\LoginService;
use Modules\Auth\Application\Services\LogoutService;
use Modules\Auth\Application\Services\PassportTokenService;
use Modules\Auth\Application\Services\RbacAuthorizationStrategy;
use Modules\Auth\Application\Services\RefreshTokenService;
use Modules\Auth\Application\Services\RegisterUserService;
use Modules\Auth\Application\Services\ResetPasswordService;
use Modules\Auth\Application\Services\SsoService;
use Modules\Auth\Application\UseCases\ForgotPassword;
use Modules\Auth\Application\UseCases\GetAuthenticatedUser;
use Modules\Auth\Application\UseCases\LoginUser;
use Modules\Auth\Application\UseCases\LogoutUser;
use Modules\Auth\Application\UseCases\RefreshToken;
use Modules\Auth\Application\UseCases\RegisterUser;
use Modules\Auth\Application\UseCases\ResetPassword;
use Modules\Auth\Infrastructure\Persistence\EloquentAuthUserRepository;

class AuthModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Auth user repository: decouples auth services from UserModel
        $this->app->bind(AuthUserRepositoryInterface::class, EloquentAuthUserRepository::class);

        // Authorization strategies (RBAC + ABAC) — resolved via interface for DIP compliance
        $this->app->bind(RbacAuthorizationStrategy::class, function ($app) {
            return new RbacAuthorizationStrategy(
                $app->make(AuthUserRepositoryInterface::class),
            );
        });

        $this->app->bind(AbacAuthorizationStrategy::class, function ($app) {
            return new AbacAuthorizationStrategy(
                $app->make(AuthUserRepositoryInterface::class),
            );
        });

        // Composite authorization service (delegates to strategies via variadic DI)
        $this->app->bind(AuthorizationServiceInterface::class, function ($app) {
            return new AuthorizationService(
                $app->make(AuthUserRepositoryInterface::class),
                $app->make(RbacAuthorizationStrategy::class),
                $app->make(AbacAuthorizationStrategy::class),
            );
        });

        // Token service: swappable via binding (default: Passport)
        $this->app->bind(TokenServiceInterface::class, function ($app) {
            return new PassportTokenService(
                $app->make(AuthUserRepositoryInterface::class),
            );
        });

        // Core auth service
        $this->app->bind(AuthenticationServiceInterface::class, function ($app) {
            return new AuthenticationService(
                $app->make(TokenServiceInterface::class),
            );
        });

        // Register / Login / Logout services
        $this->app->bind(RegisterUserServiceInterface::class, function ($app) {
            return new RegisterUserService(
                $app->make(AuthUserRepositoryInterface::class),
            );
        });

        $this->app->bind(LoginServiceInterface::class, function ($app) {
            return new LoginService(
                $app->make(AuthenticationServiceInterface::class),
                $app->make(AuthUserRepositoryInterface::class),
            );
        });

        $this->app->bind(LogoutServiceInterface::class, function ($app) {
            return new LogoutService(
                $app->make(AuthenticationServiceInterface::class),
                $app->make(AuthUserRepositoryInterface::class),
            );
        });

        // Token refresh service (token-rotation pattern)
        $this->app->bind(RefreshTokenServiceInterface::class, function ($app) {
            return new RefreshTokenService(
                $app->make(TokenServiceInterface::class),
            );
        });

        // Password reset services
        $this->app->bind(ForgotPasswordServiceInterface::class, ForgotPasswordService::class);
        $this->app->bind(ResetPasswordServiceInterface::class, ResetPasswordService::class);

        // SSO service
        $this->app->bind(SsoServiceInterface::class, function ($app) {
            return new SsoService(
                $app->make(TokenServiceInterface::class),
            );
        });

        // Use cases (resolved via container for proper DI in controllers)
        $this->app->bind(LoginUser::class, function ($app) {
            return new LoginUser(
                $app->make(LoginServiceInterface::class),
            );
        });

        $this->app->bind(LogoutUser::class, function ($app) {
            return new LogoutUser(
                $app->make(LogoutServiceInterface::class),
            );
        });

        $this->app->bind(RegisterUser::class, function ($app) {
            return new RegisterUser(
                $app->make(RegisterUserServiceInterface::class),
                $app->make(LoginServiceInterface::class),
            );
        });

        $this->app->bind(RefreshToken::class, function ($app) {
            return new RefreshToken(
                $app->make(RefreshTokenServiceInterface::class),
            );
        });

        $this->app->bind(ForgotPassword::class, function ($app) {
            return new ForgotPassword(
                $app->make(ForgotPasswordServiceInterface::class),
            );
        });

        $this->app->bind(ResetPassword::class, function ($app) {
            return new ResetPassword(
                $app->make(ResetPasswordServiceInterface::class),
            );
        });

        $this->app->bind(GetAuthenticatedUser::class, GetAuthenticatedUser::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Configure Passport token lifetime
        Passport::tokensExpireIn(now()->addDays(
            (int) config('auth.passport.token_expiry_days', 15)
        ));
        Passport::refreshTokensExpireIn(now()->addDays(
            (int) config('auth.passport.refresh_token_expiry_days', 30)
        ));
        Passport::personalAccessTokensExpireIn(now()->addMonths(
            (int) config('auth.passport.personal_token_expiry_months', 6)
        ));
    }
}
