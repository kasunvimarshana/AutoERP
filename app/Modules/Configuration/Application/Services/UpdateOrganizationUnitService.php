<?php
namespace Modules\Configuration\Application\Services;

use Modules\Configuration\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\Configuration\Application\DTOs\OrganizationUnitData;
use Modules\Configuration\Domain\Entities\OrganizationUnit;
use Modules\Configuration\Domain\Events\OrganizationUnitUpdated;
use Modules\Configuration\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class UpdateOrganizationUnitService implements UpdateOrganizationUnitServiceInterface
{
    public function __construct(private readonly OrganizationUnitRepositoryInterface $repository) {}

    public function execute(int $id, OrganizationUnitData $data): OrganizationUnit
    {
        $unit = $this->repository->findById($id);
        if (!$unit) throw new \DomainException("OrganizationUnit not found: {$id}");
        $updated = $this->repository->update($unit, $data->toArray());
        event(new OrganizationUnitUpdated($data->tenantId, $id));
        return $updated;
    }
}
