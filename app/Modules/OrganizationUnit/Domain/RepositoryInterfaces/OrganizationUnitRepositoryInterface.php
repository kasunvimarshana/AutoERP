<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;

interface OrganizationUnitRepositoryInterface extends RepositoryInterface
{
    public function save(OrganizationUnit $organizationUnit): OrganizationUnit;

    public function findByCode(int $tenantId, string $code): ?OrganizationUnit;
}
