<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Events;

class OrganizationUnitDeleted
{
    public function __construct(public int $unitId, public int $tenantId) {}
}
