<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Security;

use Modules\Core\Exceptions\ConfigurationException;

final readonly class AuthSecurityConfig
{
    /**
     * @param list<string> $oauthScopes
     */
    public function __construct(
        public int $accessTokenTtlSeconds,
        public int $refreshTokenTtlSeconds,
        public int $tenantSessionTtlSeconds,
        public int $platformSessionTtlSeconds,
        public int $authorizationCodeTtlSeconds,
        public int $activityTouchIntervalSeconds,
        public int $accountMaxAttempts,
        public int $accountIpMaxAttempts,
        public int $globalIpMaxAttempts,
        public int $loginWindowSeconds,
        public int $mfaEnrollmentProofTtlSeconds,
        public int $passwordMinimumLength,
        public array $oauthScopes,
    ) {
        $this->assertValid();
    }

    public static function fromConfig(): self
    {
        $configuredScopes = config('module-auth.oauth.scopes', []);
        if (! is_array($configuredScopes)) {
            throw new ConfigurationException('Auth OAuth scopes must be configured as a list.');
        }

        $oauthScopes = [];
        foreach ($configuredScopes as $scope) {
            if (! is_string($scope)) {
                throw new ConfigurationException('Every Auth OAuth scope must be a string.');
            }

            $scope = trim($scope);
            if ($scope === '' || preg_match('/^[a-z0-9][a-z0-9._:-]{0,119}$/', $scope) !== 1) {
                throw new ConfigurationException('Auth OAuth scopes must use stable lowercase scope keys.');
            }

            $oauthScopes[] = $scope;
        }

        self::validateSupportingConfiguration();

        return new self(
            accessTokenTtlSeconds: (int) config('module-auth.tokens.access_ttl_seconds'),
            refreshTokenTtlSeconds: (int) config('module-auth.tokens.refresh_ttl_seconds'),
            tenantSessionTtlSeconds: (int) config('module-auth.sessions.tenant_ttl_seconds'),
            platformSessionTtlSeconds: (int) config('module-auth.sessions.platform_ttl_seconds'),
            authorizationCodeTtlSeconds: (int) config('module-auth.oauth.authorization_code_ttl_seconds'),
            activityTouchIntervalSeconds: (int) config('module-auth.sessions.activity_touch_interval_seconds'),
            accountMaxAttempts: (int) config('module-auth.rate_limits.account_max_attempts'),
            accountIpMaxAttempts: (int) config('module-auth.rate_limits.account_ip_max_attempts'),
            globalIpMaxAttempts: (int) config('module-auth.rate_limits.global_ip_max_attempts'),
            loginWindowSeconds: (int) config('module-auth.rate_limits.window_seconds'),
            mfaEnrollmentProofTtlSeconds: (int) config('module-auth.platform_mfa.enrollment_proof_ttl_seconds'),
            passwordMinimumLength: (int) config('module-auth.password.minimum_length'),
            oauthScopes: array_values(array_unique($oauthScopes)),
        );
    }

    private function assertValid(): void
    {
        if ($this->accessTokenTtlSeconds < 60 || $this->accessTokenTtlSeconds > 86400) {
            throw new ConfigurationException('Auth access-token TTL must be between 60 seconds and 24 hours.');
        }

        foreach ([
            'refresh token' => $this->refreshTokenTtlSeconds,
            'tenant session' => $this->tenantSessionTtlSeconds,
            'platform session' => $this->platformSessionTtlSeconds,
        ] as $name => $ttl) {
            if ($ttl < $this->accessTokenTtlSeconds || $ttl > 7776000) {
                throw new ConfigurationException(sprintf(
                    'Auth %s TTL must be at least the access-token TTL and no more than 90 days.',
                    $name,
                ));
            }
        }

        if ($this->authorizationCodeTtlSeconds < 30 || $this->authorizationCodeTtlSeconds > 600) {
            throw new ConfigurationException('Auth authorization-code TTL must be between 30 and 600 seconds.');
        }

        if ($this->activityTouchIntervalSeconds < 30 || $this->activityTouchIntervalSeconds > 3600) {
            throw new ConfigurationException('Auth session activity interval must be between 30 and 3600 seconds.');
        }

        if (
            $this->accountMaxAttempts < 1
            || $this->accountIpMaxAttempts < $this->accountMaxAttempts
            || $this->globalIpMaxAttempts < $this->accountIpMaxAttempts
        ) {
            throw new ConfigurationException('Auth login-rate limits are inconsistent.');
        }

        if ($this->loginWindowSeconds < 60 || $this->loginWindowSeconds > 86400) {
            throw new ConfigurationException('Auth login-attempt window must be between one minute and one day.');
        }

        if ($this->mfaEnrollmentProofTtlSeconds < 60 || $this->mfaEnrollmentProofTtlSeconds > 1800) {
            throw new ConfigurationException('Auth MFA enrollment proof TTL must be between one and 30 minutes.');
        }

        if ($this->passwordMinimumLength < 12) {
            throw new ConfigurationException('Auth password minimum length cannot be below 12 characters.');
        }

        if ($this->oauthScopes === []) {
            throw new ConfigurationException('Auth OAuth scope catalogue cannot be empty.');
        }
    }

    private static function validateSupportingConfiguration(): void
    {
        foreach ([
            'internal provider key' => config('module-auth.internal_provider_key'),
            'tenant guard' => config('module-auth.protected_route_guard'),
            'platform guard' => config('module-auth.platform_protected_route_guard'),
            'tenant token guard driver' => config('module-auth.token_guard_driver'),
            'platform token guard driver' => config('module-auth.platform_token_guard_driver'),
            'platform MFA middleware alias' => config('module-auth.platform_mfa.middleware_alias'),
        ] as $name => $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new ConfigurationException(sprintf('Auth %s must be a non-empty string.', $name));
            }
        }

        $providerKey = (string) config('module-auth.internal_provider_key');
        if (preg_match('/^[a-z0-9][a-z0-9._-]{0,119}$/', $providerKey) !== 1) {
            throw new ConfigurationException('Auth internal provider key has an invalid format.');
        }

        foreach ([
            'refresh rate limit' => config('module-auth.rate_limits.refresh_per_minute'),
            'OAuth exchange rate limit' => config('module-auth.rate_limits.oauth_exchange_per_minute'),
            'invitation rate limit' => config('module-auth.rate_limits.invitations_per_minute'),
        ] as $name => $value) {
            self::assertIntegerRange($name, $value, 1, 10000);
        }

        self::assertIntegerRange(
            'platform step-up TTL',
            config('module-auth.platform_mfa.step_up_ttl_seconds'),
            60,
            3600,
        );

        $issuer = trim((string) config('module-auth.platform_mfa.issuer'));
        if ($issuer === '' || mb_strlen($issuer) > 120) {
            throw new ConfigurationException('Auth platform MFA issuer must contain 1 to 120 characters.');
        }

        self::assertIntegerRange(
            'registration invitation expiry',
            config('module-auth.registration.invitation_expiry_hours'),
            1,
            720,
        );
        $leaseSeconds = self::assertIntegerRange(
            'registration delivery lease',
            config('module-auth.registration.delivery_lease_seconds'),
            30,
            3600,
        );
        $staleSeconds = self::assertIntegerRange(
            'registration delivery stale threshold',
            config('module-auth.registration.delivery_stale_after_seconds'),
            60,
            86400,
        );
        if ($staleSeconds < $leaseSeconds) {
            throw new ConfigurationException('Auth delivery stale threshold cannot be shorter than its lease.');
        }
        $invitationUrl = trim((string) config('module-auth.registration.invitation_url'));
        if (
            $invitationUrl === ''
            || (! str_starts_with($invitationUrl, '/') && filter_var($invitationUrl, FILTER_VALIDATE_URL) === false)
        ) {
            throw new ConfigurationException('Auth registration invitation URL is invalid.');
        }

        foreach ([
            'authorization-code retention' => config('module-auth.retention.authorization_codes_days'),
            'login-attempt retention' => config('module-auth.retention.login_attempts_days'),
            'terminal-token retention' => config('module-auth.retention.terminal_tokens_days'),
            'terminal-session retention' => config('module-auth.retention.terminal_sessions_days'),
            'processed-event retention' => config('module-auth.retention.processed_events_days'),
            'invitation-delivery retention' => config('module-auth.retention.invitation_deliveries_days'),
        ] as $name => $value) {
            self::assertIntegerRange($name, $value, 1, 3650);
        }

        foreach (['tenant_refresh', 'platform_refresh'] as $cookie) {
            self::validateCookieConfiguration('module-auth.cookies.'.$cookie, $cookie);
        }
    }

    private static function validateCookieConfiguration(string $key, string $name): void
    {
        $cookieName = trim((string) config($key.'.name'));
        if ($cookieName === '' || preg_match('/[=;,\s]/', $cookieName) === 1) {
            throw new ConfigurationException(sprintf('Auth %s cookie name is invalid.', $name));
        }

        $path = trim((string) config($key.'.path'));
        if (! str_starts_with($path, '/')) {
            throw new ConfigurationException(sprintf('Auth %s cookie path must be absolute.', $name));
        }

        $sameSite = strtolower(trim((string) config($key.'.same_site')));
        if (! in_array($sameSite, ['lax', 'strict', 'none'], true)) {
            throw new ConfigurationException(sprintf('Auth %s cookie SameSite value is invalid.', $name));
        }

        if ($sameSite === 'none' && ! (bool) config($key.'.secure')) {
            throw new ConfigurationException(sprintf(
                'Auth %s cookie must be secure when SameSite=None.',
                $name,
            ));
        }
    }

    private static function assertIntegerRange(
        string $name,
        mixed $value,
        int $minimum,
        int $maximum,
    ): int {
        if (! is_int($value) && ! ctype_digit((string) $value)) {
            throw new ConfigurationException(sprintf('Auth %s must be an integer.', $name));
        }

        $value = (int) $value;
        if ($value < $minimum || $value > $maximum) {
            throw new ConfigurationException(sprintf(
                'Auth %s must be between %d and %d.',
                $name,
                $minimum,
                $maximum,
            ));
        }

        return $value;
    }
}
