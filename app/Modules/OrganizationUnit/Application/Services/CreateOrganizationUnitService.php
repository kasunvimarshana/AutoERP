<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class CreateOrganizationUnitService extends BaseService implements CreateOrganizationUnitServiceInterface
{
    public function __construct(private readonly OrganizationUnitRepositoryInterface $organizationUnitRepository)
    {
        parent::__construct($organizationUnitRepository);
    }

    protected function handle(array $data): OrganizationUnit
    {
        $dto = OrganizationUnitData::fromArray($data);

        $organizationUnit = new OrganizationUnit(
            tenantId: $dto->tenant_id,
            typeId: $dto->type_id,
            parentId: $dto->parent_id,
            managerUserId: $dto->manager_user_id,
            name: $dto->name,
            code: $dto->code,
            metadata: $dto->metadata,
            isActive: $dto->is_active ?? true,
            description: $dto->description,
        );

        return $this->organizationUnitRepository->save($organizationUnit);
    }
}
