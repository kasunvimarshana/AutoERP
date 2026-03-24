<?php

namespace Modules\OrganizationUnit\Application\UseCases;

use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Application\DTOs\MoveOrganizationUnitData;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitMoved;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;

class MoveOrganizationUnit
{
    public function __construct(
        private OrganizationUnitRepositoryInterface $orgUnitRepo
    ) {}

    public function execute(int $id, MoveOrganizationUnitData $data): void
    {
        $unit = $this->orgUnitRepo->find($id);
        if (!$unit) {
            throw new OrganizationUnitNotFoundException($id);
        }

        $oldParentId = $unit->getParentId();
        if ($oldParentId === $data->parent_id) {
            return;
        }

        $this->orgUnitRepo->moveNode($id, $data->parent_id);
        $updated = $this->orgUnitRepo->find($id);
        event(new OrganizationUnitMoved($updated, $oldParentId));
    }
}
