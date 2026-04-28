<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class DeleteOrganizationUnitService extends BaseService implements DeleteOrganizationUnitServiceInterface
{
    public function __construct(private readonly OrganizationUnitRepositoryInterface $organizationUnitRepository)
    {
        parent::__construct($organizationUnitRepository);
    }

    protected function handle(array $data): bool
    {
        $organizationUnitId = (int) $data['id'];
        $organizationUnit = $this->organizationUnitRepository->find($organizationUnitId);
        if (! $organizationUnit) {
            throw new OrganizationUnitNotFoundException($organizationUnitId);
        }

        return $this->organizationUnitRepository->delete($organizationUnitId);
    }
}
