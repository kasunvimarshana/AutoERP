<?php
namespace Modules\Authorization\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class RoleData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $guardName = 'api',
        public readonly ?string $description = null,
    ) {}
}
