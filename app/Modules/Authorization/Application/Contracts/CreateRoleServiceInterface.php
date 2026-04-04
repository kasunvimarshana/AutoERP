<?php
namespace Modules\Authorization\Application\Contracts;

use Modules\Authorization\Application\DTOs\RoleData;
use Modules\Authorization\Domain\Entities\Role;

interface CreateRoleServiceInterface
{
    public function execute(RoleData $data): Role;
}
