<?php
namespace Modules\Warehouse\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class Warehouse extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $country = null,
        public readonly bool $isDefault = false,
    ) {
        parent::__construct($id);
    }
}
