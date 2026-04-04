<?php
namespace Modules\Authorization\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Authorization\Application\Contracts\CreateRoleServiceInterface;
use Modules\Authorization\Application\DTOs\RoleData;
use Modules\Authorization\Domain\Entities\Role;
use Modules\Authorization\Domain\Events\RoleCreated;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;

class CreateRoleService implements CreateRoleServiceInterface
{
    public function __construct(private readonly RoleRepositoryInterface $repository) {}

    public function execute(RoleData $data): Role
    {
        $role = $this->repository->create([
            'tenant_id'   => $data->tenantId,
            'name'        => $data->name,
            'guard_name'  => $data->guardName,
            'description' => $data->description,
        ]);
        Event::dispatch(new RoleCreated($role->tenantId, $role->id));
        return $role;
    }
}
