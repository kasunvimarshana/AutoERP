<?php
namespace Modules\Configuration\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;

class OrganizationUnitData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $type,
        public readonly ?int $parentId = null,
        public readonly ?string $address = null,
        public readonly bool $isActive = true,
    ) {}
}
