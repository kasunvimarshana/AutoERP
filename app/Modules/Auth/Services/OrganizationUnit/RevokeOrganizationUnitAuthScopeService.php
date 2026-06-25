<?php

declare(strict_types=1);

namespace Modules\Auth\Services\OrganizationUnit;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Constants\AuthStatus;
use Modules\Core\Contracts\OrganizationUnitAuthScopeRevokerInterface;

final class RevokeOrganizationUnitAuthScopeService implements OrganizationUnitAuthScopeRevokerInterface
{
    public function revokeForUserOrganizationUnit(int $tenantId, int $userId, int $organizationUnitId): void
    {
        if ($tenantId < 1 || $userId < 1 || $organizationUnitId < 1) {
            throw new \InvalidArgumentException('Valid tenant, user, and organization-unit identifiers are required.');
        }

        $now = now();
        foreach (['auth_sessions', 'auth_access_tokens', 'auth_refresh_tokens'] as $table) {
            DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('organization_unit_id', $organizationUnitId)
                ->where('status', AuthStatus::ACTIVE)
                ->update([
                    'status' => AuthStatus::REVOKED,
                    'revoked_at' => $now,
                    'row_version' => DB::raw('row_version + 1'),
                    'updated_at' => $now,
                ]);
        }
    }
}
