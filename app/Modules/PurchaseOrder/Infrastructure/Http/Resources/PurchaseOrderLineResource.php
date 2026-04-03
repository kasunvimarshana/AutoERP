<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->getId(),
            'tenant_id'          => $this->getTenantId(),
            'purchase_order_id'  => $this->getPurchaseOrderId(),
            'line_number'        => $this->getLineNumber(),
            'product_id'         => $this->getProductId(),
            'variation_id'       => $this->getVariationId(),
            'description'        => $this->getDescription(),
            'uom_id'             => $this->getUomId(),
            'quantity_ordered'   => $this->getQuantityOrdered(),
            'quantity_received'  => $this->getQuantityReceived(),
            'unit_price'         => $this->getUnitPrice(),
            'discount_percent'   => $this->getDiscountPercent(),
            'tax_percent'        => $this->getTaxPercent(),
            'line_total'         => $this->getLineTotal(),
            'expected_date'      => $this->getExpectedDate(),
            'notes'              => $this->getNotes(),
            'metadata'           => $this->getMetadata(),
            'status'             => $this->getStatus(),
            'created_at'         => $this->getCreatedAt()->format('c'),
            'updated_at'         => $this->getUpdatedAt()->format('c'),
        ];
    }
}
