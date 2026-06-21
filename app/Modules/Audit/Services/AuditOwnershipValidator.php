<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AuditOwnershipValidator
{
    /** @return array{tenant_name:string|null,organization_unit_name:string|null} */
    public function validateSystemScope(int $tenantId, ?int $organizationUnitId): array
    {
        $tenant = DB::table('tenants')->where('id', $tenantId)->whereNull('deleted_at')->first(['name']);
        if ($tenant === null) {
            throw new InvalidArgumentException('Audit tenant scope does not exist.');
        }

        $organizationName = null;
        if ($organizationUnitId !== null) {
            $organization = DB::table('organization_units')
                ->where('id', $organizationUnitId)
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->first(['name']);

            if ($organization === null) {
                throw new InvalidArgumentException('Audit organization unit does not belong to the tenant scope.');
            }

            $organizationName = (string) $organization->name;
        }

        return [
            'tenant_name' => (string) $tenant->name,
            'organization_unit_name' => $organizationName,
        ];
    }
}
