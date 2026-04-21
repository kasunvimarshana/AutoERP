<?php

declare(strict_types=1);

namespace Modules\Sales\Application\DTOs;

class SalesInvoiceLineData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $uomId,
        public readonly ?int $salesInvoiceId = null,
        public readonly ?int $salesOrderLineId = null,
        public readonly ?int $variantId = null,
        public readonly ?string $description = null,
        public readonly string $quantity = '0.000000',
        public readonly string $unitPrice = '0.000000',
        public readonly string $discountPct = '0.000000',
        public readonly ?int $taxGroupId = null,
        public readonly string $taxAmount = '0.000000',
        public readonly string $lineTotal = '0.000000',
        public readonly ?int $incomeAccountId = null,
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
            salesInvoiceId: isset($data['sales_invoice_id']) ? (int) $data['sales_invoice_id'] : null,
            salesOrderLineId: isset($data['sales_order_line_id']) ? (int) $data['sales_order_line_id'] : null,
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            quantity: isset($data['quantity']) ? (string) $data['quantity'] : '0.000000',
            unitPrice: isset($data['unit_price']) ? (string) $data['unit_price'] : '0.000000',
            discountPct: isset($data['discount_pct']) ? (string) $data['discount_pct'] : '0.000000',
            taxGroupId: isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
            taxAmount: isset($data['tax_amount']) ? (string) $data['tax_amount'] : '0.000000',
            lineTotal: isset($data['line_total']) ? (string) $data['line_total'] : '0.000000',
            incomeAccountId: isset($data['income_account_id']) ? (int) $data['income_account_id'] : null,
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
            'sales_invoice_id' => $this->salesInvoiceId,
            'sales_order_line_id' => $this->salesOrderLineId,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'description' => $this->description,
            'uom_id' => $this->uomId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'discount_pct' => $this->discountPct,
            'tax_group_id' => $this->taxGroupId,
            'tax_amount' => $this->taxAmount,
            'line_total' => $this->lineTotal,
            'income_account_id' => $this->incomeAccountId,
        ];
    }
}
