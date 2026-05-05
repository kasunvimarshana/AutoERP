<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUserAdded;

class HandleOrganizationUnitUserAdded
{
    public function handle(OrganizationUnitUserAdded $event): void
    {
        Log::info('OrganizationUnit user added', [
            'org_unit_id' => $event->organizationUnitId,
            'tenant_id'   => $event->tenantId,
            'user_id'     => $event->userId,
            'role_id'     => $event->roleId,
            'is_primary'  => $event->isPrimary,
        ]);
    }
}
