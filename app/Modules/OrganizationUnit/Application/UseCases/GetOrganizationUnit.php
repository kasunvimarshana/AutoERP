<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\UseCases;

use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class GetOrganizationUnit
{
    public function __construct(
        private OrganizationUnitRepositoryInterface $orgUnitRepo
    ) {}

    public function execute(int $id): ?OrganizationUnit
    {
        return $this->orgUnitRepo->find($id);
    }

    public function getTree(int $tenantId, ?int $rootId = null): array
    {
        return $this->orgUnitRepo->getTree($tenantId, $rootId);
    }
}
