<?php

declare(strict_types=1);

namespace Modules\Auth\Services\OrganizationUnit;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Enums\TokenStatus;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\OrganizationUnitAuthScopeRevokerInterface;

final readonly class RevokeOrganizationUnitAuthScopeService implements OrganizationUnitAuthScopeRevokerInterface
{
    public function __construct(private ClockInterface $clock) {}

    public function revokeForUserOrganizationUnit(int $tenantId, int $userId, int $organizationUnitId): void
    {
        if ($tenantId < 1 || $userId < 1 || $organizationUnitId < 1) {
            throw new \InvalidArgumentException('Valid tenant, user, and organization-unit identifiers are required.');
        }

        $now = $this->clock->now();
        $reason = 'Organization-unit access was removed.';
        foreach (['auth_access_tokens', 'auth_refresh_tokens'] as $table) {
            DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('organization_unit_id', $organizationUnitId)
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
            ->where('organization_unit_id', $organizationUnitId)
            ->where('status', SessionStatus::ACTIVE->value)
            ->increment('row_version', 1, [
                'status' => SessionStatus::REVOKED->value,
                'revoked_at' => $now,
                'revocation_reason' => $reason,
                'updated_at' => $now,
            ]);
    }
}
