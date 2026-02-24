<?php
namespace Modules\Inventory\Domain\Entities;
class Warehouse
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly array $address,
        public readonly ?string $responsibleUserId,
        public readonly bool $isActive,
    ) {}
}
