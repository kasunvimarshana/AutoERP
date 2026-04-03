<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockReturnLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->getId(),
            'tenant_id'            => $this->getTenantId(),
            'stock_return_id'      => $this->getStockReturnId(),
            'product_id'           => $this->getProductId(),
            'variation_id'         => $this->getVariationId(),
            'batch_id'             => $this->getBatchId(),
            'serial_number_id'     => $this->getSerialNumberId(),
            'uom_id'               => $this->getUomId(),
            'quantity_requested'   => $this->getQuantityRequested(),
            'quantity_approved'    => $this->getQuantityApproved(),
            'unit_price'           => $this->getUnitPrice(),
            'unit_cost'            => $this->getUnitCost(),
            'condition'            => $this->getCondition(),
            'disposition'          => $this->getDisposition(),
            'quality_check_status' => $this->getQualityCheckStatus(),
            'quality_checked_by'   => $this->getQualityCheckedBy(),
            'quality_checked_at'   => $this->getQualityCheckedAt()?->format('c'),
            'notes'                => $this->getNotes(),
            'created_at'           => $this->getCreatedAt()->format('c'),
            'updated_at'           => $this->getUpdatedAt()->format('c'),
        ];
    }
}
