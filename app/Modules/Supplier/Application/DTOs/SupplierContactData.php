<?php
namespace Modules\Supplier\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class SupplierContactData extends BaseDTO
{
    public function __construct(
        public readonly int $supplierId,
        public readonly string $name,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $position = null,
        public readonly bool $isPrimary = false,
    ) {}
}
