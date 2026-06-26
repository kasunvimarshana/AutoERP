<?php

declare(strict_types=1);

namespace Modules\Auth\Services\UserIntegration;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Services\Credentials\PasswordCredentialService;
use Modules\Core\Contracts\ClockInterface;
use Modules\User\Contracts\TenantUserAccessRevokerInterface;

final class TenantUserAccessRevoker implements TenantUserAccessRevokerInterface
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly PasswordCredentialService $credentials,
    ) {}

    public function revokeSessionsForUser(int $tenantId, int $userId, string $reason): void
    {
        $now = $this->clock->now();
        foreach (['auth_sessions', 'auth_access_tokens', 'auth_refresh_tokens'] as $table) {
            DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => $now,
                    'row_version' => DB::raw('row_version + 1'),
                    'updated_at' => $now,
                ]);
        }
    }

    public function revokeAllForUser(int $tenantId, int $userId, string $reason): void
    {
        $this->revokeSessionsForUser($tenantId, $userId, $reason);
        $this->credentials->revokeTenantUser($tenantId, $userId);
    }
}
