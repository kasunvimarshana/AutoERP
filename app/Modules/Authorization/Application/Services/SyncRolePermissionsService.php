<?php
namespace Modules\Authorization\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Authorization\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\Authorization\Application\DTOs\SyncPermissionsData;
use Modules\Authorization\Domain\Events\RolePermissionsSynced;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;

class SyncRolePermissionsService implements SyncRolePermissionsServiceInterface
{
    public function __construct(private readonly RoleRepositoryInterface $repository) {}

    public function execute(SyncPermissionsData $data): void
    {
        $role = $this->repository->findById($data->roleId);
        if (!$role) {
            throw new \DomainException("Role [{$data->roleId}] not found.");
        }
        $this->repository->syncPermissions($role, $data->permissionIds);
        Event::dispatch(new RolePermissionsSynced($role->tenantId, $role->id, $data->permissionIds));
    }
}
