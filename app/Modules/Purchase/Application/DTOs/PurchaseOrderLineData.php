<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseOrderLineData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $purchaseOrderId,
        public readonly int $productId,
        public readonly int $uomId,
        public readonly string $orderedQty,
        public readonly string $unitPrice,
        public readonly string $receivedQty = '0',
        public readonly string $discountPct = '0',
        public readonly ?int $variantId = null,
        public readonly ?string $description = null,
        public readonly ?int $taxGroupId = null,
        public readonly ?int $accountId = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            purchaseOrderId: (int) $data['purchase_order_id'],
            productId: (int) $data['product_id'],
            uomId: (int) $data['uom_id'],
            orderedQty: (string) $data['ordered_qty'],
            unitPrice: (string) $data['unit_price'],
            receivedQty: isset($data['received_qty']) ? (string) $data['received_qty'] : '0',
            discountPct: isset($data['discount_pct']) ? (string) $data['discount_pct'] : '0',
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            taxGroupId: isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
            accountId: isset($data['account_id']) ? (int) $data['account_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'purchase_order_id' => $this->purchaseOrderId,
            'product_id' => $this->productId,
            'uom_id' => $this->uomId,
            'ordered_qty' => $this->orderedQty,
            'unit_price' => $this->unitPrice,
            'received_qty' => $this->receivedQty,
            'discount_pct' => $this->discountPct,
            'variant_id' => $this->variantId,
            'description' => $this->description,
            'tax_group_id' => $this->taxGroupId,
            'account_id' => $this->accountId,
        ];
    }
}
