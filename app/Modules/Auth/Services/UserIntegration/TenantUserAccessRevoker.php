<?php

declare(strict_types=1);

namespace Modules\Auth\Services\UserIntegration;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Enums\TokenStatus;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Core\Contracts\ClockInterface;
use Modules\User\Contracts\TenantUserAccessRevokerInterface;

final readonly class TenantUserAccessRevoker implements TenantUserAccessRevokerInterface
{
    public function __construct(
        private ClockInterface $clock,
        private PasswordCredentialService $credentials,
    ) {}

    public function revokeSessionsForUser(int $tenantId, int $userId, string $reason): void
    {
        if ($tenantId < 1 || $userId < 1 || trim($reason) === '') {
            throw new \InvalidArgumentException('Tenant, user, and revocation reason are required.');
        }

        $now = $this->clock->now();
        foreach (['auth_access_tokens', 'auth_refresh_tokens'] as $table) {
            DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('status', TokenStatus::ACTIVE->value)
                ->increment('row_version', 1, [
                    'status' => TokenStatus::REVOKED->value,
                    'revoked_at' => $now,
                    'revocation_reason' => $reason,
                    'updated_at' => $now,
                ]);
        }

        DB::table('auth_sessions')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', SessionStatus::ACTIVE->value)
            ->increment('row_version', 1, [
                'status' => SessionStatus::REVOKED->value,
                'revoked_at' => $now,
                'revocation_reason' => $reason,
                'updated_at' => $now,
            ]);
    }

    public function revokeAllForUser(int $tenantId, int $userId, string $reason): void
    {
        $this->revokeSessionsForUser($tenantId, $userId, $reason);
        $this->credentials->revokeTenantUser($tenantId, $userId);
    }
}
