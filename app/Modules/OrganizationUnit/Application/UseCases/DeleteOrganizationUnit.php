<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\UseCases;

use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class DeleteOrganizationUnit
{
    public function __construct(
        private OrganizationUnitRepositoryInterface $orgUnitRepo
    ) {}

    public function execute(int $id): bool
    {
        $unit = $this->orgUnitRepo->find($id);
        if (! $unit) {
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
