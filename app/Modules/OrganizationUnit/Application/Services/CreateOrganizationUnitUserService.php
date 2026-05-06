<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Core\Application\Services\BaseService;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitUserData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUserAdded;
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

        return DB::transaction(function () use ($dto): OrganizationUnitUser {
            $organizationUnitUser = new OrganizationUnitUser(
                tenantId: $dto->tenant_id,
                organizationUnitId: $dto->org_unit_id,
                userId: $dto->user_id,
                roleId: $dto->role_id,
                isPrimary: $dto->is_primary,
            );

            $saved = $this->organizationUnitUserRepository->save($organizationUnitUser);

            // Dispatch event
            Event::dispatch(new OrganizationUnitUserAdded(
                organizationUnitId: $saved->getOrganizationUnitId(),
                tenantId: $saved->getTenantId(),
                userId: $saved->getUserId(),
                roleId: $saved->getRole(),
                isPrimary: $saved->isPrimary(),
            ));

            return $saved;
        });
    }
}
