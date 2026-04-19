<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;

interface OrganizationUnitUserRepositoryInterface extends RepositoryInterface
{
    public function save(OrganizationUnitUser $organizationUnitUser): OrganizationUnitUser;

    public function findByTenantOrgUnitAndUser(int $tenantId, int $organizationUnitId, int $userId): ?OrganizationUnitUser;
}
