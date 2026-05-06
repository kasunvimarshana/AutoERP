<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitUserData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitUserRepositoryInterface;

class CreateOrganizationUnitUserService extends BaseService implements CreateOrganizationUnitUserServiceInterface
{
    public function __construct(private readonly OrganizationUnitUserRepositoryInterface $organizationUnitUserRepository)
    {
        parent::__construct($organizationUnitUserRepository);
    }

    protected function handle(array $data): OrganizationUnitUser
    {
        $dto = OrganizationUnitUserData::fromArray($data);

        $organizationUnitUser = new OrganizationUnitUser(
            tenantId: $dto->tenant_id,
            organizationUnitId: $dto->org_unit_id,
            userId: $dto->user_id,
            role: $dto->role,
            isPrimary: $dto->is_primary,
        );

        return $this->organizationUnitUserRepository->save($organizationUnitUser);
    }
}
