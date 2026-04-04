<?php
namespace Modules\Authorization\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Authorization\Application\Contracts\DeletePermissionServiceInterface;
use Modules\Authorization\Domain\Entities\Permission;
use Modules\Authorization\Domain\Events\PermissionDeleted;
use Modules\Authorization\Domain\RepositoryInterfaces\PermissionRepositoryInterface;

class DeletePermissionService implements DeletePermissionServiceInterface
{
    public function __construct(private readonly PermissionRepositoryInterface $repository) {}

    public function execute(Permission $permission): bool
    {
        $deleted = $this->repository->delete($permission);
        if ($deleted) {
            Event::dispatch(new PermissionDeleted(0, $permission->id));
        }
        return $deleted;
    }
}
