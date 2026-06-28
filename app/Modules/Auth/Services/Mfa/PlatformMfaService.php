<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Mfa;

use Illuminate\Database\DatabaseManager;
use Modules\Auth\Constants\PlatformMfaStatus;
use Modules\Auth\Models\AuthPlatformMfaMethodModel;
use Modules\Auth\Services\Security\AuthSecurityConfig;
use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Contracts\PlatformMfaEnrollmentIssuerInterface;

final readonly class PlatformMfaService implements PlatformMfaEnrollmentIssuerInterface
{
    private const BACKUP_CODE_COUNT = 8;
    private const BACKUP_CODE_BYTES = 6;

    public function __construct(
        private TotpService $totp,
        private PasswordHasherInterface $passwords,
        private OpaqueTokenCodec $codec,
        private AuthSecurityConfig $config,
        private ClockInterface $clock,
        private DatabaseManager $database,
        private TenantExecutionContextInterface $executionContext,
        private PlatformMfaPolicy $policy,
    ) {}

    public function issueForOperator(int $operatorId, string $email): ?array
    {
        if (! $this->policy->isEnabled()) {
            return null;
        }
        return $this->executionContext->runAsControlPlane(fn (): array => $this->database->transaction(function () use ($operatorId, $email): array {
            $method = AuthPlatformMfaMethodModel::query()
                ->where('platform_operator_id', $operatorId)->lockForUpdate()->first();
            $method ??= new AuthPlatformMfaMethodModel();
            $plainProof = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $secret = $this->totp->generateSecret();
            $method->forceFill([
                'platform_operator_id' => $operatorId,
                'secret' => $secret,
                'backup_code_hashes' => null,
                'status' => PlatformMfaStatus::PENDING,
                'enrollment_proof_digest' => $this->codec->digestArbitrary($plainProof, 'platform-mfa-enrollment'),
                'enrollment_proof_expires_at' => $this->clock->now()->modify(
                    '+'.$this->config->mfaEnrollmentProofTtlSeconds.' seconds',
                ),
                'last_totp_counter' => null,
                'confirmed_at' => null,
                'last_used_at' => null,
                'disabled_at' => null,
                'row_version' => $method->exists
                    ? (int) $method->getAttribute('row_version') + 1
                    : 1,
            ])->save();

            return [
                'enrollment_proof' => $plainProof,
                'provisioning_uri' => $this->totp->provisioningUri(
                    $secret,
                    $email,
                    (string) config('module-auth.platform_mfa.issuer', config('app.name', 'AutoERP')),
                ),
            ];
        }, 3));
    }

    /** @return array{enabled:true,backup_codes:list<string>} */
    public function confirmEnrollment(string $plainProof, string $code): array
    {
        return $this->executionContext->runAsControlPlane(fn (): array => $this->database->transaction(function () use ($plainProof, $code): array {
            $digest = $this->codec->digestArbitrary(trim($plainProof), 'platform-mfa-enrollment');
            $method = AuthPlatformMfaMethodModel::query()
                ->where('enrollment_proof_digest', $digest)->lockForUpdate()->first();
            if (! $method instanceof AuthPlatformMfaMethodModel
                || $method->getAttribute('status') !== PlatformMfaStatus::PENDING
                || $method->getAttribute('enrollment_proof_expires_at') === null
                || $this->clock->now()->getTimestamp() >= $method->getAttribute('enrollment_proof_expires_at')->getTimestamp()) {
                throw new \InvalidArgumentException('The MFA enrollment proof is invalid or expired.');
            }
            $counter = $this->totp->matchCounter(
                (string) $method->getAttribute('secret'),
                $code,
                $this->clock->now()->getTimestamp(),
            );
            if ($counter === null) {
                throw new \InvalidArgumentException('The MFA code is invalid.');
            }
            $backupCodes = $this->generateBackupCodes();
            $method->forceFill([
                'backup_code_hashes' => array_map(
                    fn (string $backup): string => $this->passwords->hash($this->normalizeBackupCode($backup)),
                    $backupCodes,
                ),
                'status' => PlatformMfaStatus::ACTIVE,
                'enrollment_proof_digest' => null,
                'enrollment_proof_expires_at' => null,
                'last_totp_counter' => $counter,
                'confirmed_at' => $this->clock->now(),
                'last_used_at' => $this->clock->now(),
                'row_version' => (int) $method->getAttribute('row_version') + 1,
            ])->save();

            return ['enabled' => true, 'backup_codes' => $backupCodes];
        }, 3));
    }

    public function isActive(int $operatorId): bool
    {
        return $this->executionContext->runAsControlPlane(fn (): bool => AuthPlatformMfaMethodModel::query()
            ->where('platform_operator_id', $operatorId)
            ->where('status', PlatformMfaStatus::ACTIVE)->exists());
    }

    public function verify(int $operatorId, ?string $totpCode, ?string $backupCode): bool
    {
        return $this->executionContext->runAsControlPlane(
            fn (): bool => $this->database->transaction(
                function () use ($operatorId, $totpCode, $backupCode): bool {
                    $method = AuthPlatformMfaMethodModel::query()
                        ->where('platform_operator_id', $operatorId)
                        ->where('status', PlatformMfaStatus::ACTIVE)
                        ->lockForUpdate()
                        ->first();

                    if (! $method instanceof AuthPlatformMfaMethodModel) {
                        return false;
                    }

                    $counter = is_string($totpCode)
                        ? $this->totp->matchCounter(
                            (string) $method->getAttribute('secret'),
                            $totpCode,
                            $this->clock->now()->getTimestamp(),
                        )
                        : null;
                    $lastCounter = $method->getAttribute('last_totp_counter');

                    if (
                        $counter !== null
                        && (! is_numeric($lastCounter) || $counter > (int) $lastCounter)
                    ) {
                        $method->forceFill([
                            'last_totp_counter' => $counter,
                            'last_used_at' => $this->clock->now(),
                            'row_version' => (int) $method->getAttribute('row_version') + 1,
                        ])->save();

                        return true;
                    }

                    $normalized = is_string($backupCode)
                        ? $this->normalizeBackupCode($backupCode)
                        : '';
                    $hashes = $method->getAttribute('backup_code_hashes');

                    if ($normalized === '' || ! is_array($hashes)) {
                        return false;
                    }

                    foreach ($hashes as $index => $hash) {
                        if (is_string($hash) && $this->passwords->verify($normalized, $hash)) {
                            unset($hashes[$index]);
                            $method->forceFill([
                                'backup_code_hashes' => array_values($hashes),
                                'last_used_at' => $this->clock->now(),
                                'row_version' => (int) $method->getAttribute('row_version') + 1,
                            ])->save();

                            return true;
                        }
                    }

                    return false;
                },
                3,
            ),
        );
    }

    public function revokeForOperator(int $operatorId): void
    {
        $this->executionContext->runAsControlPlane(fn () => $this->database->transaction(function () use ($operatorId): void {
            $method = AuthPlatformMfaMethodModel::query()
                ->where('platform_operator_id', $operatorId)->lockForUpdate()->first();
            if (! $method instanceof AuthPlatformMfaMethodModel) {
                return;
            }
            $method->forceFill([
                'backup_code_hashes' => null,
                'status' => PlatformMfaStatus::DISABLED,
                'enrollment_proof_digest' => null,
                'enrollment_proof_expires_at' => null,
                'last_totp_counter' => null,
                'disabled_at' => $this->clock->now(),
                'row_version' => (int) $method->getAttribute('row_version') + 1,
            ])->save();
        }, 3));
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

    private function normalizeBackupCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $code) ?? '');
    }
}
