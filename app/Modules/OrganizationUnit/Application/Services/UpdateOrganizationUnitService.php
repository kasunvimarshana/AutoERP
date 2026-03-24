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
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;

class UpdateOrganizationUnitService extends BaseService implements UpdateOrganizationUnitServiceInterface
{
    private OrganizationUnitRepositoryInterface $orgUnitRepository;

    public function __construct(OrganizationUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->orgUnitRepository = $repository;
    }

    protected function handle(array $data): OrganizationUnit
    {
        $id = $data['id'];
        $dto = OrganizationUnitData::fromArray($data);

        $unit = $this->orgUnitRepository->find($id);
        if (!$unit) {
            throw new OrganizationUnitNotFoundException($id);
        }

        $name = new Name($dto->name);
        $code = $dto->code !== null ? new Code($dto->code) : null;
        $metadata = $dto->metadata ? new Metadata($dto->metadata) : null;
        $unit->updateDetails($name, $code, $dto->description, $metadata);

        if ($dto->parent_id !== $unit->getParentId()) {
            $this->orgUnitRepository->moveNode($id, $dto->parent_id);
        }

        $saved = $this->orgUnitRepository->save($unit);
        $this->addEvent(new OrganizationUnitUpdated($saved));
        return $saved;
    }
}
