<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;
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

        return DB::transaction(function () use ($organizationUnitId): bool {
            $organizationUnit = $this->organizationUnitRepository->find($organizationUnitId);
            if (! $organizationUnit) {
                throw new OrganizationUnitNotFoundException($organizationUnitId);
            }

            $name = $organizationUnit->getName();
            $tenantId = $organizationUnit->getTenantId();

            $result = $this->organizationUnitRepository->delete($organizationUnitId);

            if ($result) {
                // Dispatch event
                Event::dispatch(new OrganizationUnitDeleted(
                    organizationUnitId: $organizationUnitId,
                    tenantId: $tenantId,
                    name: $name,
                ));
            }

            return $result;
        });
    }
}
