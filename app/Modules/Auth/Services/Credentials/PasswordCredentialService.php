<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Credentials;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\AuthPlatformOperatorPasswordCredentialModel;
use Modules\Auth\Models\AuthUserPasswordCredentialModel;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\User\Contracts\PlatformOperatorCredentialProvisionerInterface;
use RuntimeException;

final class PasswordCredentialService implements PlatformOperatorCredentialProvisionerInterface
{
    private const ACTIVE = 'active';
    private const REVOKED = 'revoked';

    public function __construct(
        private readonly PasswordHasherInterface $hasher,
        private readonly ClockInterface $clock,
    ) {}

    public function setTenantUserPassword(int $tenantId, int $userId, string $plainPassword): void
    {
        $this->assertPassword($plainPassword);
        DB::transaction(function () use ($tenantId, $userId, $plainPassword): void {
            $credential = AuthUserPasswordCredentialModel::query()
                ->where('tenant_id', $tenantId)->where('user_id', $userId)
                ->lockForUpdate()->first();
            if (! $credential instanceof AuthUserPasswordCredentialModel) {
                $credential = new AuthUserPasswordCredentialModel();
                $credential->forceFill(['tenant_id' => $tenantId, 'user_id' => $userId, 'row_version' => 1]);
            } else {
                $credential->setAttribute('row_version', (int) $credential->getAttribute('row_version') + 1);
            }
            $credential->forceFill([
                'password_hash' => $this->hasher->hash($plainPassword),
                'status' => self::ACTIVE,
                'changed_at' => $this->clock->now(),
                'revoked_at' => null,
            ])->save();
        }, 3);
    }

    public function verifyTenantUser(int $tenantId, int $userId, string $plainPassword): bool
    {
        $credential = AuthUserPasswordCredentialModel::query()
            ->where('tenant_id', $tenantId)->where('user_id', $userId)
            ->where('status', self::ACTIVE)->whereNull('revoked_at')->first();
        if (! $credential instanceof AuthUserPasswordCredentialModel) {
            return false;
        }
        $hash = (string) $credential->getAttribute('password_hash');
        if ($hash === '' || ! $this->hasher->verify($plainPassword, $hash)) {
            return false;
        }
        if ($this->hasher->needsRehash($hash)) {
            $credential->forceFill([
                'password_hash' => $this->hasher->hash($plainPassword),
                'changed_at' => $this->clock->now(),
                'row_version' => (int) $credential->getAttribute('row_version') + 1,
            ])->save();
        }

        return true;
    }

    public function revokeTenantUser(int $tenantId, int $userId): void
    {
        AuthUserPasswordCredentialModel::query()
            ->where('tenant_id', $tenantId)->where('user_id', $userId)
            ->where('status', self::ACTIVE)
            ->update([
                'status' => self::REVOKED,
                'revoked_at' => $this->clock->now(),
                'row_version' => DB::raw('row_version + 1'),
                'updated_at' => $this->clock->now(),
            ]);
    }

    public function provision(int $platformOperatorId, string $plainPassword): void
    {
        $this->assertPassword($plainPassword);
        DB::transaction(function () use ($platformOperatorId, $plainPassword): void {
            $credential = AuthPlatformOperatorPasswordCredentialModel::query()
                ->where('platform_operator_id', $platformOperatorId)->lockForUpdate()->first();
            if (! $credential instanceof AuthPlatformOperatorPasswordCredentialModel) {
                $credential = new AuthPlatformOperatorPasswordCredentialModel();
                $credential->forceFill(['platform_operator_id' => $platformOperatorId, 'row_version' => 1]);
            } else {
                $credential->setAttribute('row_version', (int) $credential->getAttribute('row_version') + 1);
            }
            $credential->forceFill([
                'password_hash' => $this->hasher->hash($plainPassword),
                'status' => self::ACTIVE,
                'changed_at' => $this->clock->now(),
                'revoked_at' => null,
            ])->save();
        }, 3);
    }

    public function verifyPlatformOperator(int $operatorId, string $plainPassword): bool
    {
        $credential = AuthPlatformOperatorPasswordCredentialModel::query()
            ->where('platform_operator_id', $operatorId)->where('status', self::ACTIVE)
            ->whereNull('revoked_at')->first();

        return $credential instanceof AuthPlatformOperatorPasswordCredentialModel
            && $this->hasher->verify($plainPassword, (string) $credential->getAttribute('password_hash'));
    }

    private function assertPassword(string $plainPassword): void
    {
        if (mb_strlen($plainPassword) < 12) {
            throw new RuntimeException('Password must contain at least 12 characters.');
        }
    }
}
