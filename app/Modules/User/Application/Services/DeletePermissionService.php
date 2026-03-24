<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Application\Contracts\DeletePermissionServiceInterface;

class DeletePermissionService extends BaseService implements DeletePermissionServiceInterface
{
    private PermissionRepositoryInterface $permissionRepository;

    public function __construct(PermissionRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->permissionRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        if (!$this->permissionRepository->find($id)) {
            throw new \Modules\User\Domain\Exceptions\PermissionNotFoundException($id);
        }

        return $this->permissionRepository->delete($id);
    }
}
