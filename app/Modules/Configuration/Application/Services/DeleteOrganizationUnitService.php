<?php
namespace Modules\Configuration\Application\Services;

use Modules\Configuration\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\Configuration\Domain\Events\OrganizationUnitDeleted;
use Modules\Configuration\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class DeleteOrganizationUnitService implements DeleteOrganizationUnitServiceInterface
{
    public function __construct(private readonly OrganizationUnitRepositoryInterface $repository) {}

    public function execute(int $id): bool
    {
        $unit = $this->repository->findById($id);
        if (!$unit) throw new \DomainException("OrganizationUnit not found: {$id}");
        $result = $this->repository->delete($unit);
        event(new OrganizationUnitDeleted($unit->tenantId, $id));
        return $result;
    }
}
