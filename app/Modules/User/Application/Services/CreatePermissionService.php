<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Application\Contracts\CreatePermissionServiceInterface;

class CreatePermissionService extends BaseService implements CreatePermissionServiceInterface
{
    private PermissionRepositoryInterface $permissionRepository;

    public function __construct(PermissionRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->permissionRepository = $repository;
    }

    protected function handle(array $data): Permission
    {
        $permission = new Permission(
            tenantId: $data['tenant_id'],
            name: $data['name']
        );

        return $this->permissionRepository->save($permission);
    }
}
