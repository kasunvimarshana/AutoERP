<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUserRemoved;

class HandleOrganizationUnitUserRemoved
{
    public function handle(OrganizationUnitUserRemoved $event): void
    {
        Log::info('OrganizationUnit user removed', [
            'org_unit_id' => $event->organizationUnitId,
            'tenant_id'   => $event->tenantId,
            'user_id'     => $event->userId,
        ]);
    }
}
