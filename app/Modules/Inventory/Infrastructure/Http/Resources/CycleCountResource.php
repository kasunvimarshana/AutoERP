<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\CycleCountLine;

class CycleCountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'warehouse_id' => $this->getWarehouseId(),
            'location_id' => $this->getLocationId(),
            'status' => $this->getStatus(),
            'counted_by_user_id' => $this->getCountedByUserId(),
            'counted_at' => $this->getCountedAt(),
            'approved_by_user_id' => $this->getApprovedByUserId(),
            'approved_at' => $this->getApprovedAt(),
            'lines' => array_map(static fn (CycleCountLine $line): array => [
                'id' => $line->getId(),
                'tenant_id' => $line->getTenantId(),
                'product_id' => $line->getProductId(),
                'variant_id' => $line->getVariantId(),
                'batch_id' => $line->getBatchId(),
                'serial_id' => $line->getSerialId(),
                'system_qty' => $line->getSystemQty(),
                'counted_qty' => $line->getCountedQty(),
                'variance_qty' => $line->getVarianceQty(),
                'unit_cost' => $line->getUnitCost(),
                'variance_value' => $line->getVarianceValue(),
                'adjustment_movement_id' => $line->getAdjustmentMovementId(),
            ], $this->getLines()),
        ];
    }
}
