<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitUserNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitUserRepositoryInterface;

class UpdateOrganizationUnitUserService extends BaseService implements UpdateOrganizationUnitUserServiceInterface
{
    public function __construct(private readonly OrganizationUnitUserRepositoryInterface $organizationUnitUserRepository)
    {
        parent::__construct($organizationUnitUserRepository);
    }

    protected function handle(array $data): OrganizationUnitUser
    {
        $organizationUnitUserId = (int) $data['id'];
        $organizationUnitUser = $this->organizationUnitUserRepository->find($organizationUnitUserId);
        if (! $organizationUnitUser) {
            throw new OrganizationUnitUserNotFoundException($organizationUnitUserId);
        }

        $organizationUnitUser->update(
            role: array_key_exists('role', $data) ? (is_string($data['role']) ? $data['role'] : null) : $organizationUnitUser->getRole(),
            isPrimary: array_key_exists('is_primary', $data) ? (bool) $data['is_primary'] : $organizationUnitUser->isPrimary(),
        );

        return $this->organizationUnitUserRepository->save($organizationUnitUser);
    }
}
