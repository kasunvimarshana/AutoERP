<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitType;

interface OrganizationUnitTypeRepositoryInterface extends RepositoryInterface
{
    public function save(OrganizationUnitType $organizationUnitType): OrganizationUnitType;

    public function findByTenantAndName(int $tenantId, string $name): ?OrganizationUnitType;
}
