<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitCreated;

class HandleOrganizationUnitCreated
{
    public function handle(OrganizationUnitCreated $event): void
    {
        Log::info('OrganizationUnit created', [
            'org_unit_id' => $event->organizationUnitId,
            'tenant_id'   => $event->tenantId,
            'name'        => $event->name,
            'parent_id'   => $event->parentId,
            'type_id'     => $event->typeId,
        ]);
    }
}
