<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\DTOs\ClientContext;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Models\AuthPlatformLoginAttemptModel;
use Modules\Auth\Models\AuthPlatformSessionModel;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Auth\Services\Mfa\PlatformMfaPolicy;
use Modules\Auth\Services\Mfa\PlatformMfaService;
use Modules\Auth\Services\Security\AuthSecurityConfig;
use Modules\Auth\Services\Security\LoginThrottle;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;

final readonly class PlatformAuthenticationService
{
    private const REALM = 'platform';

    public function __construct(
        private DatabaseManager $database,
        private ClockInterface $clock,
        private TenantExecutionContextInterface $executionContext,
        private PlatformOperatorAuthenticationDirectoryInterface $operators,
        private PasswordCredentialService $credentials,
        private PlatformMfaService $mfa,
        private PlatformMfaPolicy $mfaPolicy,
        private LoginThrottle $throttle,
        private TokenService $tokens,
        private AuthSecurityConfig $config,
    ) {}

    /** @return array<string,mixed> */
    public function login(
        string $email,
        string $password,
        ?string $totpCode,
        ?string $backupCode,
        ClientContext $client,
    ): array {
        $email = mb_strtolower(trim($email));
        $operator = null;

        if ($email === '' || $this->throttle->isBlocked(self::REALM, $email, $client->ipAddress)) {
            $this->credentials->verifyDummy($password);
            $this->recordAttempt(null, $email, false, 'rate_limited', $client);
            throw $this->invalidCredentials();
        }

        $operator = $this->operators->findPlatformForLogin($email);
        $validOperator = $operator !== null
            && (string) ($operator['status'] ?? '') === 'active'
            && (bool) ($operator['credentials_ready'] ?? false);
        $passwordValid = $validOperator
            ? $this->credentials->verifyPlatformOperator((int) $operator['id'], $password)
            : false;

        if (! $validOperator || ! $passwordValid) {
            if (! $validOperator) {
                $this->credentials->verifyDummy($password);
            }
            $this->throttle->recordFailure(self::REALM, $email, $client->ipAddress);
            $this->recordAttempt($operator === null ? null : (int) $operator['id'], $email, false, AuthErrorCode::INVALID_CREDENTIALS, $client);
            throw $this->invalidCredentials();
        }

        $operatorId = (int) $operator['id'];
        $mfaVerifiedAt = null;
        if ($this->mfaPolicy->isEnabled()) {
            if (! $this->mfa->isActive($operatorId)) {
                throw new AuthFailure(
                    AuthErrorCode::MFA_ENROLLMENT_REQUIRED,
                    'Multi-factor authentication enrollment is required.',
                    409,
                );
            }

            if ($this->mfaPolicy->shouldChallengeLogin(true)) {
                if ($totpCode === null && $backupCode === null) {
                    throw new AuthFailure(AuthErrorCode::MFA_REQUIRED, 'A multi-factor authentication code is required.', 401);
                }
                if (! $this->mfa->verify($operatorId, $totpCode, $backupCode)) {
                    $this->throttle->recordFailure(self::REALM, $email, $client->ipAddress);
                    $this->recordAttempt($operatorId, $email, false, AuthErrorCode::MFA_INVALID_CODE, $client);
                    throw new AuthFailure(AuthErrorCode::MFA_INVALID_CODE, 'The multi-factor authentication code is invalid.', 401);
                }
                $mfaVerifiedAt = $this->clock->now();
            }
        }

        $payload = $this->executionContext->runAsControlPlane(fn (): array => $this->database->transaction(
            function () use ($operatorId, $mfaVerifiedAt, $client): array {
                $now = $this->clock->now();
                $session = AuthPlatformSessionModel::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'platform_operator_id' => $operatorId,
                    'status' => SessionStatus::ACTIVE->value,
                    'ip_address' => $client->ipAddress,
                    'user_agent' => $client->userAgent,
                    'device_name' => $client->deviceName,
                    'authenticated_at' => $now,
                    'mfa_verified_at' => $mfaVerifiedAt,
                    'last_activity_at' => $now,
                    'expires_at' => $now->modify('+'.$this->config->platformSessionTtlSeconds.' seconds'),
                    'row_version' => 1,
                ]);

                return [
                    'tokens' => $this->tokens->issuePlatformSessionTokens((int) $session->getKey(), $operatorId),
                    'session' => [
                        'id' => (string) $session->getAttribute('public_id'),
                        'expires_at' => $session->getAttribute('expires_at')?->format(DATE_ATOM),
                    ],
                ];
            },
            3,
        ));

        $this->throttle->clearSuccessful(self::REALM, $email, $client->ipAddress);
        $this->recordAttempt($operatorId, $email, true, null, $client);

        return $payload;
    }

    private function recordAttempt(
        ?int $operatorId,
        string $identifier,
        bool $successful,
        ?string $failureCode,
        ClientContext $client,
    ): void {
        try {
            $this->executionContext->runAsControlPlane(fn () => AuthPlatformLoginAttemptModel::query()->create([
                'platform_operator_id' => $operatorId,
                'login_identifier_hash' => hash_hmac('sha256', $identifier, (string) config('app.key')),
                'was_successful' => $successful,
                'failure_code' => $failureCode,
                'ip_address' => $client->ipAddress,
                'user_agent' => $client->userAgent,
                'attempted_at' => $this->clock->now(),
            ]));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function invalidCredentials(): AuthFailure
    {
        return new AuthFailure(AuthErrorCode::INVALID_CREDENTIALS, 'The credentials are invalid.', 401);
    }
}
