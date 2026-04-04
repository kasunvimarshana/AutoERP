<?php
namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\InventorySerial;

class InventorySerialResource extends JsonResource
{
    public function __construct(private readonly InventorySerial $serial)
    {
        parent::__construct($serial);
    }

    public function toArray($request): array
    {
        return [
            'id'                   => $this->serial->id,
            'tenant_id'            => $this->serial->tenantId,
            'product_id'           => $this->serial->productId,
            'serial_number'        => $this->serial->serialNumber,
            'status'               => $this->serial->status,
            'current_warehouse_id' => $this->serial->currentWarehouseId,
            'current_location_id'  => $this->serial->currentLocationId,
            'batch_id'             => $this->serial->batchId,
            'warranty_expires_at'  => $this->serial->warrantyExpiresAt?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
