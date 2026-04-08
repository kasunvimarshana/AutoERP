<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventorySettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->getId(),
            'tenant_id'            => $this->getTenantId(),
            'valuation_method'     => $this->getValuationMethod(),
            'management_method'    => $this->getManagementMethod(),
            'rotation_strategy'    => $this->getRotationStrategy(),
            'allocation_algorithm' => $this->getAllocationAlgorithm(),
            'cycle_count_method'   => $this->getCycleCountMethod(),
            'negative_stock_allowed'=> $this->isNegativeStockAllowed(),
            'track_lots'           => $this->isTrackLots(),
            'track_serial_numbers' => $this->isTrackSerialNumbers(),
            'track_expiry'         => $this->isTrackExpiry(),
            'auto_reorder'         => $this->isAutoReorder(),
            'low_stock_alert'      => $this->isLowStockAlert(),
            'metadata'             => $this->getMetadata()->toArray(),
            'is_active'            => $this->isActive(),
            'created_at'           => $this->getCreatedAt()->format('c'),
            'updated_at'           => $this->getUpdatedAt()->format('c'),
        ];
    }
}
