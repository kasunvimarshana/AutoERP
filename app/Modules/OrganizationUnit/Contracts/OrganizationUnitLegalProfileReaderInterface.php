<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Contracts;

use Modules\OrganizationUnit\DTOs\OrganizationUnitLegalProfileSnapshot;

interface OrganizationUnitLegalProfileReaderInterface
{
    public function find(int $tenantId, int $organizationUnitId): ?OrganizationUnitLegalProfileSnapshot;
}
