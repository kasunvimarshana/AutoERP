<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;

class DeleteOrganizationUnitService extends BaseService implements DeleteOrganizationUnitServiceInterface
{
    private OrganizationUnitRepositoryInterface $orgUnitRepository;

    public function __construct(OrganizationUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->orgUnitRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        $unit = $this->orgUnitRepository->find($id);
        if (! $unit) {
            throw new OrganizationUnitNotFoundException($id);
        }
        $tenantId = $unit->getTenantId();
        $deleted = $this->orgUnitRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new OrganizationUnitDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
