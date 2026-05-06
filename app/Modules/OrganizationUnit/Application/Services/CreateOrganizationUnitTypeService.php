<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitTypeData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitType;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitTypeRepositoryInterface;

class CreateOrganizationUnitTypeService extends BaseService implements CreateOrganizationUnitTypeServiceInterface
{
    public function __construct(private readonly OrganizationUnitTypeRepositoryInterface $organizationUnitTypeRepository)
    {
        parent::__construct($organizationUnitTypeRepository);
    }

    protected function handle(array $data): OrganizationUnitType
    {
        $dto = OrganizationUnitTypeData::fromArray($data);

        $organizationUnitType = new OrganizationUnitType(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            level: $dto->level,
            isActive: $dto->is_active,
        );

        return $this->organizationUnitTypeRepository->save($organizationUnitType);
    }
}
