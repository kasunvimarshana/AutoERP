<?php

declare(strict_types=1);

namespace Modules\Sales\Application\DTOs;

class SalesOrderLineData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $uomId,
        public readonly ?int $salesOrderId = null,
        public readonly ?int $variantId = null,
        public readonly ?string $description = null,
        public readonly string $orderedQty = '0.000000',
        public readonly string $shippedQty = '0.000000',
        public readonly string $reservedQty = '0.000000',
        public readonly string $unitPrice = '0.000000',
        public readonly string $discountPct = '0.000000',
        public readonly ?int $taxGroupId = null,
        public readonly string $lineTotal = '0.000000',
        public readonly ?int $incomeAccountId = null,
        public readonly ?int $batchId = null,
        public readonly ?int $serialId = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            productId: (int) $data['product_id'],
            uomId: (int) $data['uom_id'],
            salesOrderId: isset($data['sales_order_id']) ? (int) $data['sales_order_id'] : null,
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            orderedQty: isset($data['ordered_qty']) ? (string) $data['ordered_qty'] : '0.000000',
            shippedQty: isset($data['shipped_qty']) ? (string) $data['shipped_qty'] : '0.000000',
            reservedQty: isset($data['reserved_qty']) ? (string) $data['reserved_qty'] : '0.000000',
            unitPrice: isset($data['unit_price']) ? (string) $data['unit_price'] : '0.000000',
            discountPct: isset($data['discount_pct']) ? (string) $data['discount_pct'] : '0.000000',
            taxGroupId: isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
            lineTotal: isset($data['line_total']) ? (string) $data['line_total'] : '0.000000',
            incomeAccountId: isset($data['income_account_id']) ? (int) $data['income_account_id'] : null,
            batchId: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            serialId: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'sales_order_id' => $this->salesOrderId,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'description' => $this->description,
            'uom_id' => $this->uomId,
            'ordered_qty' => $this->orderedQty,
            'shipped_qty' => $this->shippedQty,
            'reserved_qty' => $this->reservedQty,
            'unit_price' => $this->unitPrice,
            'discount_pct' => $this->discountPct,
            'tax_group_id' => $this->taxGroupId,
            'line_total' => $this->lineTotal,
            'income_account_id' => $this->incomeAccountId,
            'batch_id' => $this->batchId,
            'serial_id' => $this->serialId,
        ];
    }
}
