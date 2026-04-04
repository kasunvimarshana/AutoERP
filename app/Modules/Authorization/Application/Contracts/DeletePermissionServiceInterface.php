<?php
namespace Modules\Authorization\Application\Contracts;

use Modules\Authorization\Domain\Entities\Permission;

interface DeletePermissionServiceInterface
{
    public function execute(Permission $permission): bool;
}
