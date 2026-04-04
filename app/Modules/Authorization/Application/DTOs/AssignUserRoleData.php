<?php
namespace Modules\Authorization\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class AssignUserRoleData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly int $roleId,
    ) {}
}
