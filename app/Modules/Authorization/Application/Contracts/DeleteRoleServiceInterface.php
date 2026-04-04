<?php
namespace Modules\Authorization\Application\Contracts;

use Modules\Authorization\Domain\Entities\Role;

interface DeleteRoleServiceInterface
{
    public function execute(Role $role): bool;
}
