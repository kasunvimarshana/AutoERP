<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUserRemoved;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitUserNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitUserRepositoryInterface;

class DeleteOrganizationUnitUserService extends BaseService implements DeleteOrganizationUnitUserServiceInterface
{
    public function __construct(private readonly OrganizationUnitUserRepositoryInterface $organizationUnitUserRepository)
    {
        parent::__construct($organizationUnitUserRepository);
    }

    protected function handle(array $data): bool
    {
        $organizationUnitUserId = (int) $data['id'];

        return DB::transaction(function () use ($organizationUnitUserId): bool {
            $organizationUnitUser = $this->organizationUnitUserRepository->find($organizationUnitUserId);
            if (! $organizationUnitUser || $organizationUnitUser->getId() === null) {
                throw new OrganizationUnitUserNotFoundException($organizationUnitUserId);
            }

            $organizationUnitId = $organizationUnitUser->getOrganizationUnitId();
            $tenantId = $organizationUnitUser->getTenantId();
            $userId = $organizationUnitUser->getUserId();

            $result = $this->organizationUnitUserRepository->delete($organizationUnitUser->getId());

            if ($result) {
                // Dispatch event
                Event::dispatch(new OrganizationUnitUserRemoved(
                    organizationUnitId: $organizationUnitId,
                    tenantId: $tenantId,
                    userId: $userId,
                ));
            }

            return $result;
        });
    }
}
