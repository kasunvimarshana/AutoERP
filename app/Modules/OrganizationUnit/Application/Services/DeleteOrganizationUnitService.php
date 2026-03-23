<?php

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;

class DeleteOrganizationUnitService extends BaseService
{
    public function __construct(OrganizationUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        $unit = $this->repository->find($id);
        if (!$unit) {
            throw new \RuntimeException('Organization unit not found');
        }
        $tenantId = $unit->getTenantId();
        $deleted = $this->repository->delete($id);
        if ($deleted) {
            $this->addEvent(new OrganizationUnitDeleted($id, $tenantId));
        }
        return $deleted;
    }
}
