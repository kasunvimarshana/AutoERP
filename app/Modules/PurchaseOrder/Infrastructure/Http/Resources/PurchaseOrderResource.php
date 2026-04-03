<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->getId(),
            'tenant_id'          => $this->getTenantId(),
            'reference_number'   => $this->getReferenceNumber(),
            'status'             => $this->getStatus(),
            'supplier_id'        => $this->getSupplierId(),
            'supplier_reference' => $this->getSupplierReference(),
            'order_date'         => $this->getOrderDate(),
            'expected_date'      => $this->getExpectedDate(),
            'warehouse_id'       => $this->getWarehouseId(),
            'currency'           => $this->getCurrency(),
            'subtotal'           => $this->getSubtotal(),
            'tax_amount'         => $this->getTaxAmount(),
            'discount_amount'    => $this->getDiscountAmount(),
            'total_amount'       => $this->getTotalAmount(),
            'notes'              => $this->getNotes(),
            'metadata'           => $this->getMetadata()->toArray(),
            'approved_by'        => $this->getApprovedBy(),
            'approved_at'        => $this->getApprovedAt()?->format('c'),
            'submitted_by'       => $this->getSubmittedBy(),
            'submitted_at'       => $this->getSubmittedAt()?->format('c'),
            'created_at'         => $this->getCreatedAt()->format('c'),
            'updated_at'         => $this->getUpdatedAt()->format('c'),
        ];
    }
}
