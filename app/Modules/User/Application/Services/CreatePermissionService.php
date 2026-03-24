<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\CreatePermissionServiceInterface;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;

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
