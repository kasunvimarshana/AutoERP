<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Console\Commands\AuthClientCreateCommand;
use Modules\Auth\Console\Commands\AuthIncidentLookupCommand;
use Modules\Auth\Console\Commands\AuthReadinessCommand;
use Modules\Auth\Console\Commands\AuthRetentionPurgeCommand;
use Modules\Auth\Contracts\PasswordHasherInterface;
use Modules\Auth\Enums\AuthScope;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Middleware\RequireRecentPlatformAuthenticationMiddleware;
use Modules\Auth\Listeners\RevokeTenantAccessOnStatusChange;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Auth\Services\CurrentUserContextResolver;
use Modules\Auth\Services\OrganizationUnit\AuthOrganizationUnitLifecycleBlocker;
use Modules\Auth\Services\OrganizationUnit\RevokeOrganizationUnitAuthScopeService;
use Modules\Auth\Services\PlatformSessionService;
use Modules\Auth\Services\Readiness\AuthReadinessService;
use Modules\Auth\Services\Provisioning\TenantAuthenticationProvisioner;
use Modules\Auth\Services\Registration\InvitationDeliveryHealthReader;
use Modules\Auth\Services\Registration\RegistrationInvitationService;
use Modules\Auth\Services\Security\AuthSecurityConfig;
use Modules\Auth\Services\Security\AccountLoginThrottle;
use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Auth\Services\Security\PasswordHasher;
use Modules\Auth\Services\PlatformTokenService;
use Modules\Auth\Services\TenantTokenService;
use Modules\Auth\Services\AccessTokenRouter;
use Modules\Auth\Services\UserIntegration\TenantUserAccessRevoker;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Core\Contracts\AuthInvitationDeliveryHealthReaderInterface;
use Modules\Core\Contracts\CurrentUserContextResolverInterface;
use Modules\Core\Contracts\OrganizationUnitAuthScopeRevokerInterface;
use Modules\Tenant\Events\TenantStatusChanged;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;
use Modules\User\Contracts\AuthenticationPrincipalProviderInterface;
use Modules\User\Contracts\PlatformOperatorCredentialProvisionerInterface;
use Modules\User\Contracts\PlatformOperatorSessionRevokerInterface;
use Modules\User\Contracts\TenantUserAccessRevokerInterface;
use Modules\User\Contracts\TenantUserInvitationIssuerInterface;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/auth.php', 'module-auth');
        $this->app->bind(PasswordHasherInterface::class, PasswordHasher::class);

        $this->app->singleton(AuthSecurityConfig::class, static fn (): AuthSecurityConfig => AuthSecurityConfig::fromConfig());
        $this->app->singleton(OpaqueTokenCodec::class, fn (): OpaqueTokenCodec => new OpaqueTokenCodec(
            (string) $this->app['config']->get('app.key'),
        ));

        $this->app->scoped(AuthInvitationDeliveryHealthReaderInterface::class, InvitationDeliveryHealthReader::class);
        $this->app->scoped(CurrentUserContextResolverInterface::class, CurrentUserContextResolver::class);
        $this->app->scoped(TenantTokenService::class);
        $this->app->scoped(PlatformTokenService::class);
        $this->app->scoped(AccessTokenRouter::class);
        $this->app->scoped(AccountLoginThrottle::class);
        $this->app->scoped(AuthReadinessService::class);
        $this->app->scoped(RegistrationInvitationService::class);
        $this->app->scoped(PasswordCredentialService::class);

        $this->app->scoped(TenantUserInvitationIssuerInterface::class, RegistrationInvitationService::class);
        $this->app->scoped(TenantUserAccessRevokerInterface::class, TenantUserAccessRevoker::class);
        $this->app->scoped(PlatformOperatorCredentialProvisionerInterface::class, PasswordCredentialService::class);
        $this->app->scoped(TenantAuthenticationProvisionerInterface::class, TenantAuthenticationProvisioner::class);
        $this->app->scoped(PlatformOperatorSessionRevokerInterface::class, PlatformSessionService::class);
        $this->app->scoped(OrganizationUnitAuthScopeRevokerInterface::class, RevokeOrganizationUnitAuthScopeService::class);

        $this->app->tag([AuthOrganizationUnitLifecycleBlocker::class], 'organization-unit.lifecycle_blocker');
    }

    public function boot(): void
    {
        $this->app->make(AuthSecurityConfig::class);
        $this->app->make(ConfigurationDefinitionRegistryInterface::class)->register(
            'Auth',
            require __DIR__.'/../Config/configuration-definitions.php',
        );

        $this->configureRateLimiters();
        $this->configureGuards();
        $this->resetResolvedBearerGuardsWhenRequestChanges();
        $this->app->make(Router::class)->aliasMiddleware(
            (string) config('module-auth.platform_step_up.middleware_alias', 'platform.step-up'),
            RequireRecentPlatformAuthenticationMiddleware::class,
        );

        Event::listen(TenantStatusChanged::class, RevokeTenantAccessOnStatusChange::class);

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            // Artisan constructs every registered command while bootstrapping. Runtime
            // services therefore belong in handle() method injection, not constructors.
            $this->commands([
                AuthClientCreateCommand::class,
                AuthIncidentLookupCommand::class,
                AuthReadinessCommand::class,
                AuthRetentionPurgeCommand::class,
            ]);
            $this->publishes([
                __DIR__.'/../Config/auth.php' => config_path('module-auth.php'),
            ], 'auth-config');
        }
    }

    private function resetResolvedBearerGuardsWhenRequestChanges(): void
    {
        $this->app->rebinding('request', static function ($app, Request $request): void {
            if ($app->resolved('auth') && trim((string) $request->bearerToken()) !== '') {
                $app['auth']->forgetGuards();
            }
        });
    }

    private function configureGuards(): void
    {
        Auth::viaRequest(
            (string) config('module-auth.token_guard_driver', 'module-auth-token'),
            function (Request $request) {
                $payload = $this->validatedPayload($request);
                if (($payload['token_scope'] ?? null) !== AuthScope::TENANT->value) {
                    return null;
                }
                $tenantId = $this->positiveInt($payload['tenant_id'] ?? null);
                $userId = $this->positiveInt($payload['tenant_user_id'] ?? null);
                return $tenantId === null || $userId === null
                    ? null
                    : $this->app->make(AuthenticationPrincipalProviderInterface::class)
                        ->tenantPrincipal($tenantId, $userId);
            },
        );

        Auth::viaRequest(
            (string) config('module-auth.platform_token_guard_driver', 'module-platform-token'),
            function (Request $request) {
                $payload = $this->validatedPayload($request);
                if (($payload['token_scope'] ?? null) !== AuthScope::PLATFORM->value) {
                    return null;
                }
                $operatorId = $this->positiveInt($payload['platform_operator_id'] ?? null);
                return $operatorId === null
                    ? null
                    : $this->app->make(AuthenticationPrincipalProviderInterface::class)
                        ->platformPrincipal($operatorId);
            },
        );
    }

    /** @return array<string,mixed> */
    private function validatedPayload(Request $request): array
    {
        $existing = $request->attributes->get('auth_access_token');
        if (is_array($existing)) {
            return $existing;
        }

        $plainToken = $request->bearerToken();
        if (! is_string($plainToken) || trim($plainToken) === '') {
            return [];
        }

        try {
            $payload = $this->app->make(AccessTokenRouter::class)->validate($plainToken);
            $request->attributes->set('auth_access_token', $payload);
            return $payload;
        } catch (AuthFailure) {
            return [];
        }
    }

    private function configureRateLimiters(): void
    {
        $windowMinutes = max(1, (int) ceil(((int) config('module-auth.rate_limits.window_seconds', 900)) / 60));
        $globalMax = max(1, (int) config('module-auth.rate_limits.global_ip_max_attempts', 30));

        RateLimiter::for('auth.tenant.login', static fn (Request $request) => Limit::perMinutes($windowMinutes, $globalMax)
            ->by('tenant-login:'.(string) $request->ip()));
        RateLimiter::for('auth.platform.login', static fn (Request $request) => Limit::perMinutes($windowMinutes, $globalMax)
            ->by('platform-login:'.(string) $request->ip()));
        RateLimiter::for('auth.tenant.refresh', static fn (Request $request) => Limit::perMinute(
            max(1, (int) config('module-auth.rate_limits.refresh_per_minute', 30)),
        )->by('tenant-refresh:'.(string) $request->ip()));
        RateLimiter::for('auth.platform.refresh', static fn (Request $request) => Limit::perMinute(
            max(1, (int) config('module-auth.rate_limits.refresh_per_minute', 30)),
        )->by('platform-refresh:'.(string) $request->ip()));
        RateLimiter::for('auth.oauth.exchange', static fn (Request $request) => Limit::perMinute(
            max(1, (int) config('module-auth.rate_limits.oauth_exchange_per_minute', 30)),
        )->by('oauth-exchange:'.(string) $request->ip()));
        RateLimiter::for('auth.invitations', static fn (Request $request) => Limit::perMinute(
            max(1, (int) config('module-auth.rate_limits.invitations_per_minute', 10)),
        )->by('auth-invitation:'.(string) $request->ip()));
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_int($value) && ! ctype_digit((string) $value)) {
            return null;
        }
        $value = (int) $value;
        return $value > 0 ? $value : null;
    }
}
