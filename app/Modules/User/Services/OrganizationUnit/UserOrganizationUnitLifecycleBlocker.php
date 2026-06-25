<?php

declare(strict_types=1);

namespace Modules\User\Services\OrganizationUnit;

use Illuminate\Support\Facades\DB;
use Modules\OrganizationUnit\Contracts\OrganizationUnitLifecycleBlockerInterface;
use Modules\User\Constants\UserOrganizationUnitStatus;

final class UserOrganizationUnitLifecycleBlocker implements OrganizationUnitLifecycleBlockerInterface
{
    public function blockers(int $tenantId, int $organizationUnitId): array
    {
        $activeAssignments = DB::table('user_organization_units')
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('status', UserOrganizationUnitStatus::ACTIVE)
            ->count();

        return $activeAssignments > 0 ? [[
            'code' => 'ACTIVE_USER_ASSIGNMENTS',
            'message' => 'Reassign or revoke active user memberships before changing this organization unit lifecycle.',
            'count' => $activeAssignments,
        ]] : [];
    }
}
