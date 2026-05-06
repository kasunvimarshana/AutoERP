<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitTypeNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitTypeRepositoryInterface;

class DeleteOrganizationUnitTypeService extends BaseService implements DeleteOrganizationUnitTypeServiceInterface
{
    public function __construct(private readonly OrganizationUnitTypeRepositoryInterface $organizationUnitTypeRepository)
    {
        parent::__construct($organizationUnitTypeRepository);
    }

    protected function handle(array $data): bool
    {
        $organizationUnitTypeId = (int) $data['id'];
        $organizationUnitType = $this->organizationUnitTypeRepository->find($organizationUnitTypeId);
        if (! $organizationUnitType || $organizationUnitType->getId() === null) {
            throw new OrganizationUnitTypeNotFoundException($organizationUnitTypeId);
        }

        return $this->organizationUnitTypeRepository->delete($organizationUnitType->getId());
    }
}
