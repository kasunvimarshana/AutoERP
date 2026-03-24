<?php

namespace Modules\OrganizationUnit\Domain\Events;

class OrganizationUnitDeleted
{
    public function __construct(public int $unitId, public int $tenantId) {}
}
