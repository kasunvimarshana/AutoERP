<?php
namespace Modules\Authorization\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Authorization\Application\Contracts\CreatePermissionServiceInterface;
use Modules\Authorization\Application\DTOs\PermissionData;
use Modules\Authorization\Domain\Entities\Permission;
use Modules\Authorization\Domain\Events\PermissionCreated;
use Modules\Authorization\Domain\RepositoryInterfaces\PermissionRepositoryInterface;

class CreatePermissionService implements CreatePermissionServiceInterface
{
    public function __construct(private readonly PermissionRepositoryInterface $repository) {}

    public function execute(PermissionData $data): Permission
    {
        $permission = $this->repository->create([
            'name'        => $data->name,
            'guard_name'  => $data->guardName,
            'description' => $data->description,
        ]);
        Event::dispatch(new PermissionCreated(0, $permission->id));
        return $permission;
    }
}
