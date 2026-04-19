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
use Modules\Auth\Application\Contracts\TenantContextResolverInterface;
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
use Modules\Auth\Infrastructure\Services\TenantContextResolver;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;

class AuthModuleServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->singleton(TenantContextResolverInterface::class, TenantContextResolver::class);

        $this->app->bind(AuthUserRepositoryInterface::class, EloquentAuthUserRepository::class);
        $this->app->when(EloquentAuthUserRepository::class)
            ->needs('$userModelClass')
            ->give(static fn (): string => (string) config('auth.providers.users.model'));

        $this->app->bind(RbacAuthorizationStrategy::class, RbacAuthorizationStrategy::class);
        $this->app->bind(AbacAuthorizationStrategy::class, AbacAuthorizationStrategy::class);
        $this->app->bind(AuthorizationServiceInterface::class, AuthorizationService::class);
        $this->app->alias(AuthorizationServiceInterface::class, 'auth.authorization');

        $serviceBindings = [
            TokenServiceInterface::class => PassportTokenService::class,
            AuthenticationServiceInterface::class => AuthenticationService::class,
            RegisterUserServiceInterface::class => RegisterUserService::class,
            LoginServiceInterface::class => LoginService::class,
            LogoutServiceInterface::class => LogoutService::class,
            RefreshTokenServiceInterface::class => RefreshTokenService::class,
            SsoServiceInterface::class => SsoService::class,
        ];

        foreach ($serviceBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        // Password reset services
        $this->app->bind(ForgotPasswordServiceInterface::class, ForgotPasswordService::class);
        $this->app->bind(ResetPasswordServiceInterface::class, ResetPasswordService::class);

        $useCaseBindings = [
            LoginUser::class => LoginUser::class,
            LogoutUser::class => LogoutUser::class,
            RegisterUser::class => RegisterUser::class,
            RefreshToken::class => RefreshToken::class,
            ForgotPassword::class => ForgotPassword::class,
            ResetPassword::class => ResetPassword::class,
        ];

        foreach ($useCaseBindings as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }

        $this->app->bind(GetAuthenticatedUser::class, GetAuthenticatedUser::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );

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
