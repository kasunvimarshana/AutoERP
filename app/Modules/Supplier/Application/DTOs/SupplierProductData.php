<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\DTOs;

class SupplierProductData
{
    public function __construct(
        public readonly int $supplierId,
        public readonly int $productId,
        public readonly ?int $variantId = null,
        public readonly ?string $supplierSku = null,
        public readonly ?int $leadTimeDays = null,
        public readonly string $minOrderQty = '1.000000',
        public readonly bool $isPreferred = false,
        public readonly ?string $lastPurchasePrice = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            supplierId: (int) $data['supplier_id'],
            productId: (int) $data['product_id'],
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            supplierSku: isset($data['supplier_sku']) ? (string) $data['supplier_sku'] : null,
            leadTimeDays: isset($data['lead_time_days']) ? (int) $data['lead_time_days'] : null,
            minOrderQty: isset($data['min_order_qty']) ? (string) $data['min_order_qty'] : '1.000000',
            isPreferred: (bool) ($data['is_preferred'] ?? false),
            lastPurchasePrice: isset($data['last_purchase_price']) ? (string) $data['last_purchase_price'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
