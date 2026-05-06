<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitUserServiceInterface;
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
        $organizationUnitUser = $this->organizationUnitUserRepository->find($organizationUnitUserId);
        if (! $organizationUnitUser || $organizationUnitUser->getId() === null) {
            throw new OrganizationUnitUserNotFoundException($organizationUnitUserId);
        }

        return $this->organizationUnitUserRepository->delete($organizationUnitUser->getId());
    }
}
