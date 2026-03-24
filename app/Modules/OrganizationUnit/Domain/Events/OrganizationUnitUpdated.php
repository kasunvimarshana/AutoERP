<?php

namespace Modules\OrganizationUnit\Domain\Events;

use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;

class OrganizationUnitUpdated
{
    public function __construct(public OrganizationUnit $unit) {}
}
