<?php
namespace Modules\Authorization\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class SyncPermissionsData extends BaseDTO
{
    public function __construct(
        public readonly int $roleId,
        public readonly array $permissionIds,
    ) {}
}
