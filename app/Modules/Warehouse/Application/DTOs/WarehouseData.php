<?php
namespace Modules\Warehouse\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class WarehouseData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type = 'standard',
        public readonly string $status = 'active',
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $country = null,
        public readonly bool $isDefault = false,
    ) {}
}
