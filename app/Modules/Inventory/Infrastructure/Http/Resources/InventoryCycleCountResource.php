<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryCycleCountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->getId(),
            'tenant_id'        => $this->getTenantId(),
            'reference_number' => $this->getReferenceNumber(),
            'warehouse_id'     => $this->getWarehouseId(),
            'zone_id'          => $this->getZoneId(),
            'location_id'      => $this->getLocationId(),
            'count_method'     => $this->getCountMethod(),
            'status'           => $this->getStatus(),
            'assigned_to'      => $this->getAssignedTo(),
            'scheduled_at'     => $this->getScheduledAt()?->format('c'),
            'started_at'       => $this->getStartedAt()?->format('c'),
            'completed_at'     => $this->getCompletedAt()?->format('c'),
            'notes'            => $this->getNotes(),
            'metadata'         => $this->getMetadata()->toArray(),
            'created_at'       => $this->getCreatedAt()->format('c'),
            'updated_at'       => $this->getUpdatedAt()->format('c'),
        ];
    }
}
