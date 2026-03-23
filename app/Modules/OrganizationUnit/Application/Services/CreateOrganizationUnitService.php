<?php

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\ValueObjects\Name;
use Modules\OrganizationUnit\Domain\ValueObjects\Code;
use Modules\OrganizationUnit\Domain\ValueObjects\Metadata;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitCreated;

class CreateOrganizationUnitService extends BaseService
{
    public function __construct(OrganizationUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): OrganizationUnit
    {
        $dto = OrganizationUnitData::fromArray($data);

        $name = new Name($dto->name);
        $code = new Code($dto->code);
        $metadata = $dto->metadata ? new Metadata($dto->metadata) : null;

        $unit = new OrganizationUnit(
            tenantId: $dto->tenant_id,
            name: $name,
            code: $code,
            description: $dto->description,
            metadata: $metadata,
            parentId: $dto->parent_id
        );

        $saved = $this->repository->save($unit);
        $this->addEvent(new OrganizationUnitCreated($saved));
        return $saved;
    }
}
