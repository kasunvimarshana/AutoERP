<?php
namespace Modules\StockMovement\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\StockMovement\Domain\Entities\StockMovement;

class StockMovementResource extends JsonResource
{
    public function __construct(private readonly StockMovement $movement)
    {
        parent::__construct($movement);
    }

    public function toArray($request): array
    {
        return [
            'id'                  => $this->movement->id,
            'tenant_id'           => $this->movement->tenantId,
            'product_id'          => $this->movement->productId,
            'warehouse_id'        => $this->movement->warehouseId,
            'location_id'         => $this->movement->locationId,
            'movement_type'       => $this->movement->movementType,
            'quantity'            => $this->movement->quantity,
            'reference_number'    => $this->movement->referenceNumber,
            'variant_id'          => $this->movement->variantId,
            'batch_id'            => $this->movement->batchId,
            'lot_number'          => $this->movement->lotNumber,
            'serial_number'       => $this->movement->serialNumber,
            'unit_cost'           => $this->movement->unitCost,
            'related_movement_id' => $this->movement->relatedMovementId,
            'notes'               => $this->movement->notes,
            'moved_at'            => $this->movement->movedAt?->format('Y-m-d\TH:i:s\Z'),
            'moved_by'            => $this->movement->movedBy,
        ];
    }
}
