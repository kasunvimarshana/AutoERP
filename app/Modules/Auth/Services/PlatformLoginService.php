<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Cache\RateLimiter;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\AuthTokenScope;
use Modules\Auth\Contracts\Providers\TokenProviderInterface;
use Modules\Auth\DTOs\TokenIssueData;
use Modules\Auth\Services\Mfa\PlatformMfaService;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\User\Repositories\UserRepositoryInterface;
use Throwable;

final class PlatformLoginService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $passwords,
        private readonly TokenProviderInterface $tokens,
        private readonly RateLimiter $limiter,
        private readonly ErrorNormalizerInterface $errors,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly PlatformPermissionCheckerInterface $platformPermissions,
        private readonly PlatformMfaService $mfa,
        private readonly PlatformSessionService $sessions,
        private readonly TransactionManagerInterface $transactions,
        private readonly ClockInterface $clock,
    ) {}

    public function login(
        string $email,
        string $password,
        string $ipAddress,
        ?string $totpCode = null,
        ?string $backupCode = null,
        ?string $userAgent = null,
        ?string $deviceName = null,
    ): Result {
        return $this->executionContext->runAsControlPlane(
            fn (): Result => $this->loginAsPlatformOperator(
                $email,
                $password,
                $ipAddress,
                $totpCode,
                $backupCode,
                $userAgent,
                $deviceName,
            ),
        );
    }

    private function loginAsPlatformOperator(
        string $email,
        string $password,
        string $ipAddress,
        ?string $totpCode,
        ?string $backupCode,
        ?string $userAgent,
        ?string $deviceName,
    ): Result {
        $email = strtolower(trim($email));
        $rateKey = 'platform-login:'.hash('sha256', $email.'|'.$ipAddress);
        $maxAttempts = max(1, (int) config('module-auth.max_login_attempts', 5));
        $decaySeconds = max(60, (int) config('module-auth.login_attempt_window_seconds', 900));

        if ($this->limiter->tooManyAttempts($rateKey, $maxAttempts)) {
            return Result::failure(new Error(
                AuthErrorCode::INVALID_CREDENTIALS,
                'Platform sign-in is temporarily unavailable. Try again later.',
            ));
        }

        try {
            $operator = $this->users->findActivePlatformOperatorCredentials($email);
            if (
                $operator === null
                || ! $this->passwords->verify($password, (string) $operator->get('password_hash', ''))
            ) {
                $this->limiter->hit($rateKey, $decaySeconds);

                return Result::failure(new Error(
                    AuthErrorCode::INVALID_CREDENTIALS,
                    'The platform credentials are invalid.',
                ));
            }

            $operatorId = (int) $operator->id();
            $mfaIsActive = $this->mfa->isActive($operatorId);
            $mfaIsRequired = (bool) config('module-auth.platform_mfa.required', true);

            if ($mfaIsRequired && ! $mfaIsActive) {
                return Result::failure(new Error(
                    AuthErrorCode::MFA_ENROLLMENT_REQUIRED,
                    'Multi-factor authentication enrollment is required before platform sign-in.',
                    ['enrollment_required' => true],
                ));
            }

            if ($mfaIsActive) {
                if (($totpCode === null || trim($totpCode) === '')
                    && ($backupCode === null || trim($backupCode) === '')
                ) {
                    return Result::failure(new Error(
                        AuthErrorCode::MFA_REQUIRED,
                        'A multi-factor authentication code is required.',
                        ['mfa_required' => true],
                    ));
                }

                if (! $this->mfa->verify($operatorId, $totpCode, $backupCode)) {
                    $this->limiter->hit($rateKey, $decaySeconds);

                    return Result::failure(new Error(
                        AuthErrorCode::MFA_INVALID_CODE,
                        'The multi-factor authentication code is invalid.',
                        ['mfa_required' => true],
                    ));
                }
            }

            $this->limiter->clear($rateKey);
            $authenticatedAt = $this->clock->now()->getTimestamp();
            $metadata = [
                'application_id' => 'platform',
                'authenticated_at' => $authenticatedAt,
            ];
            if ($mfaIsActive) {
                $metadata['mfa_verified_at'] = $authenticatedAt;
            }

            $refreshTtl = (int) config('module-auth.refresh_token_ttl_seconds', 2592000);
            $tokenPair = $this->transactions->runInTransaction(function () use (
                $operatorId,
                $ipAddress,
                $userAgent,
                $deviceName,
                $refreshTtl,
                $metadata,
            ): array {
                $session = $this->sessions->create(
                    $operatorId,
                    $ipAddress,
                    $userAgent,
                    $deviceName,
                    $refreshTtl,
                );

                return $this->tokens->issue(TokenIssueData::fromArray([
                    'tenant_id' => null,
                    'organization_unit_id' => null,
                    'platform_session_id' => (int) $session->getKey(),
                    'user_id' => $operatorId,
                    'token_scope' => AuthTokenScope::PLATFORM,
                    'grant_type' => 'platform_password',
                    'scopes' => ['platform'],
                    'access_token_ttl_seconds' => (int) config('module-auth.access_token_ttl_seconds', 3600),
                    'refresh_token_ttl_seconds' => $refreshTtl,
                    'metadata' => $metadata,
                ]));
            });

            return Result::success([
                'tokens' => $tokenPair,
                'user' => [
                    'id' => $operatorId,
                    'first_name' => $operator->get('first_name'),
                    'last_name' => $operator->get('last_name'),
                    'email' => $operator->get('email'),
                    'is_platform_operator' => true,
                ],
                'tenant' => null,
                'organization_unit' => null,
                'roles' => ['Platform Operator'],
                'permissions' => $this->platformPermissions->permissions($operatorId),
                'enabled_modules' => null,
                'is_platform_operator' => true,
                'mfa_enabled' => $mfaIsActive,
            ]);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                AuthErrorCode::UNAUTHORIZED_ACCESS,
                ['operation' => 'platform.login'],
            ));
        }
    }
}
