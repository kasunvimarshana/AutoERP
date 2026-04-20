<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Purchase\Domain\Entities\PurchaseInvoiceLine;

class PurchaseInvoiceLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PurchaseInvoiceLine $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'purchase_invoice_id' => $entity->getPurchaseInvoiceId(),
            'grn_line_id' => $entity->getGrnLineId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'description' => $entity->getDescription(),
            'uom_id' => $entity->getUomId(),
            'quantity' => $entity->getQuantity(),
            'unit_price' => $entity->getUnitPrice(),
            'discount_pct' => $entity->getDiscountPct(),
            'tax_group_id' => $entity->getTaxGroupId(),
            'tax_amount' => $entity->getTaxAmount(),
            'line_total' => $entity->getLineTotal(),
            'account_id' => $entity->getAccountId(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
