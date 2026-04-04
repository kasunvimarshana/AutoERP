<?php
namespace Modules\Authorization\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Authorization\Application\Contracts\AssignUserRoleServiceInterface;
use Modules\Authorization\Application\DTOs\AssignUserRoleData;
use Modules\Authorization\Domain\Entities\UserRole;
use Modules\Authorization\Domain\Events\UserRoleAssigned;
use Modules\Authorization\Domain\RepositoryInterfaces\UserRoleRepositoryInterface;

class AssignUserRoleService implements AssignUserRoleServiceInterface
{
    public function __construct(private readonly UserRoleRepositoryInterface $repository) {}

    public function execute(AssignUserRoleData $data): UserRole
    {
        $userRole = $this->repository->assign($data->userId, $data->roleId);
        Event::dispatch(new UserRoleAssigned($data->tenantId, $data->userId, $data->roleId));
        return $userRole;
    }
}
