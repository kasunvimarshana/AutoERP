<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUpdated;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class HandleOrganizationUnitUpdated
{
    public function __construct(
        private readonly OrganizationUnitRepositoryInterface $repository,
    ) {}

    public function handle(OrganizationUnitUpdated $event): void
    {
        Log::info('OrganizationUnit updated', [
            'org_unit_id' => $event->organizationUnitId,
            'tenant_id'   => $event->tenantId,
            'name'        => $event->name,
            'parent_id'   => $event->parentId,
            'is_active'   => $event->isActive,
        ]);
    }
}
