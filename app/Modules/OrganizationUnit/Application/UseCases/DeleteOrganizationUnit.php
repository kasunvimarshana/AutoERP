<?php

namespace Modules\OrganizationUnit\Application\UseCases;

use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;

class DeleteOrganizationUnit
{
    public function __construct(
        private OrganizationUnitRepositoryInterface $orgUnitRepo
    ) {}

    public function execute(int $id): bool
    {
        $unit = $this->orgUnitRepo->find($id);
        if (!$unit) {
            throw new OrganizationUnitNotFoundException($id);
        }

        $tenantId = $unit->getTenantId();
        $deleted = $this->orgUnitRepo->delete($id);

        if ($deleted) {
            event(new OrganizationUnitDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
