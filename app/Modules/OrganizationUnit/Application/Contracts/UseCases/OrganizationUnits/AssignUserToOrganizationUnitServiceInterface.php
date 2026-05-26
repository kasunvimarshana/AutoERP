<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts\UseCases\OrganizationUnits;

use Modules\Core\Application\Results\Result;

interface AssignUserToOrganizationUnitServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $organizationUnitId, array $payload): Result;
}
