<?php
namespace Modules\Supplier\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class SupplierContact extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $supplierId,
        public readonly string $name,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $position = null,
        public readonly bool $isPrimary = false,
    ) {
        parent::__construct($id);
    }
}
