<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\MoveOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\MoveOrganizationUnitData;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitMoved;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class MoveOrganizationUnitService extends BaseService implements MoveOrganizationUnitServiceInterface
{
    private OrganizationUnitRepositoryInterface $orgUnitRepository;

    public function __construct(OrganizationUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->orgUnitRepository = $repository;
    }

    protected function handle(array $data): mixed
    {
        $id = $data['id'];
        $dto = MoveOrganizationUnitData::fromArray($data);

        $unit = $this->orgUnitRepository->find($id);
        if (! $unit) {
            throw new OrganizationUnitNotFoundException($id);
        }

        $oldParentId = $unit->getParentId();
        if ($oldParentId === $dto->parent_id) {
            return null;
        }

        $this->orgUnitRepository->moveNode($id, $dto->parent_id);
        $updated = $this->orgUnitRepository->find($id);
        $this->addEvent(new OrganizationUnitMoved($updated, $oldParentId));

        return null;
    }
}
