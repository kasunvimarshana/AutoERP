<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class UomConversionData
{
    public function __construct(
        public readonly int $from_uom_id,
        public readonly int $to_uom_id,
        public readonly string $factor,
        public readonly ?int $tenant_id = null,
        public readonly ?int $product_id = null,
        public readonly bool $is_bidirectional = true,
        public readonly bool $is_active = true,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            from_uom_id: (int) $data['from_uom_id'],
            to_uom_id: (int) $data['to_uom_id'],
            factor: (string) $data['factor'],
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            product_id: isset($data['product_id']) ? (int) $data['product_id'] : null,
            is_bidirectional: isset($data['is_bidirectional']) ? (bool) $data['is_bidirectional'] : true,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
