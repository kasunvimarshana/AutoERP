<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\CreatePermissionServiceInterface;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;

class CreatePermissionService extends BaseService implements CreatePermissionServiceInterface
{
    public function __construct(private readonly PermissionRepositoryInterface $permissionRepository)
    {
        parent::__construct($permissionRepository);
    }

    protected function handle(array $data): Permission
    {
        $permission = new Permission(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name']
        );

        return $this->permissionRepository->save($permission);
    }
}
