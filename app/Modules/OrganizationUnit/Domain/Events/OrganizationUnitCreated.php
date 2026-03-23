<?php

namespace Modules\OrganizationUnit\Domain\Events;

use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;

class OrganizationUnitCreated
{
    public function __construct(public OrganizationUnit $unit) {}
}
