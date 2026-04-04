<?php
namespace Modules\Configuration\Application\Services;

use Modules\Configuration\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\Configuration\Application\DTOs\OrganizationUnitData;
use Modules\Configuration\Domain\Entities\OrganizationUnit;
use Modules\Configuration\Domain\Events\OrganizationUnitCreated;
use Modules\Configuration\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class CreateOrganizationUnitService implements CreateOrganizationUnitServiceInterface
{
    public function __construct(private readonly OrganizationUnitRepositoryInterface $repository) {}

    public function execute(OrganizationUnitData $data): OrganizationUnit
    {
        $unit = $this->repository->create($data->toArray());
        event(new OrganizationUnitCreated($data->tenantId, $unit->id));
        return $unit;
    }
}
