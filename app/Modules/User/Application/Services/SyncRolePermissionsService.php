<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\User\Domain\Exceptions\RoleNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;

class SyncRolePermissionsService extends BaseService implements SyncRolePermissionsServiceInterface
{
    public function __construct(private readonly RoleRepositoryInterface $roleRepository)
    {
        parent::__construct($roleRepository);
    }

    protected function handle(array $data): mixed
    {
        $roleId = (int) $data['role_id'];
        $permissionIds = array_values(array_unique(array_filter(
            array_map('intval', $data['permission_ids'] ?? []),
            static fn (int $permissionId): bool => $permissionId > 0
        )));

        $role = $this->roleRepository->find($roleId);
        if (! $role) {
            throw new RoleNotFoundException($roleId);
        }

        $this->roleRepository->syncPermissions($role, $permissionIds);

        return $this->roleRepository->find($roleId);
    }
}
