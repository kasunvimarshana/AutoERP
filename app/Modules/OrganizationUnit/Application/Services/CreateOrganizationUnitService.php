<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitCreated;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class CreateOrganizationUnitService extends BaseService implements CreateOrganizationUnitServiceInterface
{
    private OrganizationUnitRepositoryInterface $orgUnitRepository;

    public function __construct(OrganizationUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->orgUnitRepository = $repository;
    }

    protected function handle(array $data): OrganizationUnit
    {
        $dto = OrganizationUnitData::fromArray($data);

        $name = new Name($dto->name);
        $code = $dto->code !== null ? new Code($dto->code) : null;
        $metadata = $dto->metadata ? new Metadata($dto->metadata) : null;

        $unit = new OrganizationUnit(
            tenantId: $dto->tenant_id,
            name: $name,
            code: $code,
            description: $dto->description,
            metadata: $metadata,
            parentId: $dto->parent_id
        );

        $saved = $this->orgUnitRepository->save($unit);
        $this->addEvent(new OrganizationUnitCreated($saved));

        return $saved;
    }
}
