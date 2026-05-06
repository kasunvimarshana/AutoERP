<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseInvoiceLineData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $purchaseInvoiceId,
        public readonly int $productId,
        public readonly int $uomId,
        public readonly string $quantity,
        public readonly string $unitPrice,
        public readonly string $lineTotal,
        public readonly string $discountPct = '0',
        public readonly string $taxAmount = '0',
        public readonly ?int $grnLineId = null,
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
            purchaseInvoiceId: (int) $data['purchase_invoice_id'],
            productId: (int) $data['product_id'],
            uomId: (int) $data['uom_id'],
            quantity: (string) $data['quantity'],
            unitPrice: (string) $data['unit_price'],
            lineTotal: (string) $data['line_total'],
            discountPct: isset($data['discount_pct']) ? (string) $data['discount_pct'] : '0',
            taxAmount: isset($data['tax_amount']) ? (string) $data['tax_amount'] : '0',
            grnLineId: isset($data['grn_line_id']) ? (int) $data['grn_line_id'] : null,
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
            'purchase_invoice_id' => $this->purchaseInvoiceId,
            'product_id' => $this->productId,
            'uom_id' => $this->uomId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal,
            'discount_pct' => $this->discountPct,
            'tax_amount' => $this->taxAmount,
            'grn_line_id' => $this->grnLineId,
            'variant_id' => $this->variantId,
            'description' => $this->description,
            'tax_group_id' => $this->taxGroupId,
            'account_id' => $this->accountId,
        ];
    }
}
