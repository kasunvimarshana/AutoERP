<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;

class HandleOrganizationUnitDeleted
{
    public function handle(OrganizationUnitDeleted $event): void
    {
        Log::info('OrganizationUnit deleted', [
            'org_unit_id' => $event->organizationUnitId,
            'tenant_id'   => $event->tenantId,
            'name'        => $event->name,
        ]);
    }
}
