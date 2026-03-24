<?php

namespace Modules\OrganizationUnit\Domain\Events;

use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;

class OrganizationUnitMoved
{
    public function __construct(
        public OrganizationUnit $unit,
        public ?int $previousParentId
    ) {}
}
