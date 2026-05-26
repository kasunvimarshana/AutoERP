<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts\UseCases\OrganizationUnits;

use Modules\Core\Application\Results\Result;

interface ResolveOrganizationUnitServiceInterface
{
    public function execute(?int $organizationUnitId = null): Result;
}
