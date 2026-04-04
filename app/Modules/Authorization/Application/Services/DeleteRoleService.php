<?php
namespace Modules\Authorization\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Authorization\Application\Contracts\DeleteRoleServiceInterface;
use Modules\Authorization\Domain\Entities\Role;
use Modules\Authorization\Domain\Events\RoleDeleted;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;

class DeleteRoleService implements DeleteRoleServiceInterface
{
    public function __construct(private readonly RoleRepositoryInterface $repository) {}

    public function execute(Role $role): bool
    {
        $deleted = $this->repository->delete($role);
        if ($deleted) {
            Event::dispatch(new RoleDeleted($role->tenantId, $role->id));
        }
        return $deleted;
    }
}
