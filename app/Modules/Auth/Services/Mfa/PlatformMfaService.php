<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Mfa;

use Illuminate\Cache\RateLimiter;
use Illuminate\Database\DatabaseManager;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\PlatformMfaStatus;
use Modules\Auth\Models\AuthPlatformMfaMethodModel;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Models\PlatformOperatorModel;
use Throwable;

final class PlatformMfaService
{
    private const BACKUP_CODE_COUNT = 8;
    private const BACKUP_CODE_BYTES = 6;

    public function __construct(
        private readonly PlatformOperatorModel $operators,
        private readonly PasswordCredentialService $credentials,
        private readonly PasswordHasherInterface $passwords,
        private readonly TotpService $totp,
        private readonly DatabaseManager $database,
        private readonly RateLimiter $limiter,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function startEnrollment(string $email, string $password, string $ipAddress): Result
    {
        return $this->executionContext->runAsControlPlane(function () use ($email, $password, $ipAddress): Result {
            $operator = $this->authenticateOperator($email, $password, $ipAddress, 'platform-mfa-enroll');
            if ($operator->isFailure()) {
                return $operator;
            }
            /** @var array{id:int,email:string} $identity */
            $identity = $operator->valueOrFail();

            try {
                $method = $this->database->transaction(function () use ($identity): AuthPlatformMfaMethodModel {
                    $method = AuthPlatformMfaMethodModel::query()
                        ->where('platform_operator_id', $identity['id'])->lockForUpdate()->first();
                    if ($method instanceof AuthPlatformMfaMethodModel
                        && $method->getAttribute('status') === PlatformMfaStatus::ACTIVE) {
                        return $method;
                    }
                    $method ??= new AuthPlatformMfaMethodModel();
                    $method->forceFill([
                        'platform_operator_id' => $identity['id'],
                        'secret' => $this->totp->generateSecret(),
                        'backup_code_hashes' => null,
                        'status' => PlatformMfaStatus::PENDING,
                        'confirmed_at' => null,
                        'last_used_at' => null,
                        'row_version' => $method->exists
                            ? (int) $method->getAttribute('row_version') + 1
                            : 1,
                    ])->save();

                    return $method;
                }, 3);
                if ($method->getAttribute('status') === PlatformMfaStatus::ACTIVE) {
                    return Result::failure(new Error(
                        AuthErrorCode::MFA_ALREADY_ENABLED,
                        'Multi-factor authentication is already enabled for this platform account.',
                    ));
                }
                $secret = (string) $method->getAttribute('secret');

                return Result::success([
                    'secret' => $secret,
                    'provisioning_uri' => $this->totp->provisioningUri(
                        $secret,
                        $identity['email'],
                        (string) config('module-auth.platform_mfa.issuer', config('app.name', 'AutoERP')),
                    ),
                ]);
            } catch (Throwable) {
                return Result::failure(new Error(
                    AuthErrorCode::MFA_ENROLLMENT_FAILED,
                    'Multi-factor authentication enrollment could not be started.',
                ));
            }
        });
    }

    public function confirmEnrollment(
        string $email,
        string $password,
        string $code,
        string $ipAddress,
    ): Result {
        return $this->executionContext->runAsControlPlane(function () use ($email, $password, $code, $ipAddress): Result {
            $operator = $this->authenticateOperator($email, $password, $ipAddress, 'platform-mfa-confirm');
            if ($operator->isFailure()) {
                return $operator;
            }
            /** @var array{id:int,email:string} $identity */
            $identity = $operator->valueOrFail();

            try {
                return $this->database->transaction(function () use ($identity, $code): Result {
                    $method = AuthPlatformMfaMethodModel::query()
                        ->where('platform_operator_id', $identity['id'])->lockForUpdate()->first();
                    if (! $method instanceof AuthPlatformMfaMethodModel
                        || $method->getAttribute('status') !== PlatformMfaStatus::PENDING
                        || ! $this->totp->verify((string) $method->getAttribute('secret'), $code)) {
                        return Result::failure(new Error(
                            AuthErrorCode::MFA_INVALID_CODE,
                            'The multi-factor authentication code is invalid.',
                        ));
                    }
                    $plainBackupCodes = $this->generateBackupCodes();
                    $method->forceFill([
                        'backup_code_hashes' => array_map(
                            fn (string $backupCode): string => $this->passwords->hash($this->normalizeBackupCode($backupCode)),
                            $plainBackupCodes,
                        ),
                        'status' => PlatformMfaStatus::ACTIVE,
                        'confirmed_at' => now(),
                        'last_used_at' => now(),
                        'row_version' => (int) $method->getAttribute('row_version') + 1,
                    ])->save();

                    return Result::success(['enabled' => true, 'backup_codes' => $plainBackupCodes]);
                }, 3);
            } catch (Throwable) {
                return Result::failure(new Error(
                    AuthErrorCode::MFA_ENROLLMENT_FAILED,
                    'Multi-factor authentication enrollment could not be confirmed.',
                ));
            }
        });
    }

    public function isActive(int $operatorId): bool
    {
        return AuthPlatformMfaMethodModel::query()
            ->where('platform_operator_id', $operatorId)
            ->where('status', PlatformMfaStatus::ACTIVE)->exists();
    }

    public function verify(int $operatorId, ?string $totpCode, ?string $backupCode): bool
    {
        return $this->database->transaction(function () use ($operatorId, $totpCode, $backupCode): bool {
            $method = AuthPlatformMfaMethodModel::query()
                ->where('platform_operator_id', $operatorId)
                ->where('status', PlatformMfaStatus::ACTIVE)->lockForUpdate()->first();
            if (! $method instanceof AuthPlatformMfaMethodModel) {
                return false;
            }
            $verified = is_string($totpCode)
                && $this->totp->verify((string) $method->getAttribute('secret'), $totpCode);
            $remainingHashes = $method->getAttribute('backup_code_hashes');
            $normalizedBackup = is_string($backupCode) ? $this->normalizeBackupCode($backupCode) : '';
            if (! $verified && $normalizedBackup !== '' && is_array($remainingHashes)) {
                foreach ($remainingHashes as $index => $hash) {
                    if (is_string($hash) && $this->passwords->verify($normalizedBackup, $hash)) {
                        unset($remainingHashes[$index]);
                        $remainingHashes = array_values($remainingHashes);
                        $verified = true;
                        break;
                    }
                }
            }
            if (! $verified) {
                return false;
            }
            $method->forceFill([
                'backup_code_hashes' => $remainingHashes,
                'last_used_at' => now(),
                'row_version' => (int) $method->getAttribute('row_version') + 1,
            ])->save();

            return true;
        }, 3);
    }

    private function authenticateOperator(string $email, string $password, string $ipAddress, string $purpose): Result
    {
        $email = strtolower(trim($email));
        $rateKey = $purpose.':'.hash('sha256', $email.'|'.$ipAddress);
        $maxAttempts = max(1, (int) config('module-auth.max_login_attempts', 5));
        $decaySeconds = max(60, (int) config('module-auth.login_attempt_window_seconds', 900));
        if ($this->limiter->tooManyAttempts($rateKey, $maxAttempts)) {
            return Result::failure(new Error(
                AuthErrorCode::INVALID_CREDENTIALS,
                'Platform authentication is temporarily unavailable. Try again later.',
            ));
        }
        $operator = $this->operators->newQuery()
            ->where('email', $email)
            ->where('status', PlatformOperatorStatus::ACTIVE)
            ->whereNotNull('credentials_ready_at')->first();
        if (! $operator instanceof PlatformOperatorModel
            || ! $this->credentials->verifyPlatformOperator((int) $operator->getKey(), $password)) {
            $this->limiter->hit($rateKey, $decaySeconds);

            return Result::failure(new Error(
                AuthErrorCode::INVALID_CREDENTIALS,
                'The platform credentials are invalid.',
            ));
        }
        $this->limiter->clear($rateKey);

        return Result::success([
            'id' => (int) $operator->getKey(),
            'email' => (string) $operator->getAttribute('email'),
        ]);
    }

    /** @return list<string> */
    private function generateBackupCodes(): array
    {
        $codes = [];
        for ($index = 0; $index < self::BACKUP_CODE_COUNT; $index++) {
            $raw = strtoupper(bin2hex(random_bytes(self::BACKUP_CODE_BYTES)));
            $codes[] = substr($raw, 0, 4).'-'.substr($raw, 4, 4).'-'.substr($raw, 8, 4);
        }

        return $codes;
    }

    private function normalizeBackupCode(string $backupCode): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $backupCode) ?? '');
    }
}
