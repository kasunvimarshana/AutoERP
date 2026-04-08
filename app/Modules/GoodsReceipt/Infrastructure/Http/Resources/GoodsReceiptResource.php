<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->getId(),
            'tenant_id'         => $this->getTenantId(),
            'reference_number'  => $this->getReferenceNumber(),
            'status'            => $this->getStatus(),
            'purchase_order_id' => $this->getPurchaseOrderId(),
            'supplier_id'       => $this->getSupplierId(),
            'warehouse_id'      => $this->getWarehouseId(),
            'received_date'     => $this->getReceivedDate()?->format('Y-m-d'),
            'currency'          => $this->getCurrency(),
            'notes'             => $this->getNotes(),
            'metadata'          => $this->getMetadata()->toArray(),
            'received_by'       => $this->getReceivedBy(),
            'approved_by'       => $this->getApprovedBy(),
            'approved_at'       => $this->getApprovedAt()?->format('c'),
            'inspected_by'      => $this->getInspectedBy(),
            'inspected_at'      => $this->getInspectedAt()?->format('c'),
            'put_away_by'       => $this->getPutAwayBy(),
            'created_at'        => $this->getCreatedAt()->format('c'),
            'updated_at'        => $this->getUpdatedAt()->format('c'),
        ];
    }
}
