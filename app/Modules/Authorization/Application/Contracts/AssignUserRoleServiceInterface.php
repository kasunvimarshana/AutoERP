<?php
namespace Modules\Authorization\Application\Contracts;

use Modules\Authorization\Application\DTOs\AssignUserRoleData;
use Modules\Authorization\Domain\Entities\UserRole;

interface AssignUserRoleServiceInterface
{
    public function execute(AssignUserRoleData $data): UserRole;
}
