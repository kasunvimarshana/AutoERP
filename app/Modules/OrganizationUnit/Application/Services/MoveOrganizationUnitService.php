<?php

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Application\DTOs\MoveOrganizationUnitData;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitMoved;

class MoveOrganizationUnitService extends BaseService
{
    public function __construct(OrganizationUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): void
    {
        $id = $data['id'];
        $dto = MoveOrganizationUnitData::fromArray($data);

        $unit = $this->repository->find($id);
        if (!$unit) {
            throw new \RuntimeException('Organization unit not found');
        }

        $oldParentId = $unit->getParentId();
        if ($oldParentId === $dto->parent_id) {
            return;
        }

        $this->repository->moveNode($id, $dto->parent_id);
        $updated = $this->repository->find($id);
        $this->addEvent(new OrganizationUnitMoved($updated, $oldParentId));
    }
}
