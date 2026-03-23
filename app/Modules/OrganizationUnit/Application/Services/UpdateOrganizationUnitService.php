<?php

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\ValueObjects\Name;
use Modules\OrganizationUnit\Domain\ValueObjects\Code;
use Modules\OrganizationUnit\Domain\ValueObjects\Metadata;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUpdated;

class UpdateOrganizationUnitService extends BaseService
{
    public function __construct(OrganizationUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): OrganizationUnit
    {
        $id = $data['id'];
        $dto = OrganizationUnitData::fromArray($data);

        $unit = $this->repository->find($id);
        if (!$unit) {
            throw new \RuntimeException('Organization unit not found');
        }

        $name = new Name($dto->name);
        $code = new Code($dto->code);
        $metadata = $dto->metadata ? new Metadata($dto->metadata) : null;
        $unit->updateDetails($name, $code, $dto->description, $metadata);

        // If parent changed, move node
        if ($dto->parent_id !== $unit->getParentId()) {
            $this->repository->moveNode($id, $dto->parent_id);
        }

        $saved = $this->repository->save($unit);
        $this->addEvent(new OrganizationUnitUpdated($saved));
        return $saved;
    }
}
