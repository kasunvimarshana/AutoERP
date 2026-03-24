<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\DeletePermissionServiceInterface;
use Modules\User\Domain\Exceptions\PermissionNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;

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
        if (! $this->permissionRepository->find($id)) {
            throw new PermissionNotFoundException($id);
        }

        return $this->permissionRepository->delete($id);
    }
}
