<?php
namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\InventoryBatch;

class InventoryBatchResource extends JsonResource
{
    public function __construct(private readonly InventoryBatch $batch)
    {
        parent::__construct($batch);
    }

    public function toArray($request): array
    {
        return [
            'id'                 => $this->batch->id,
            'tenant_id'          => $this->batch->tenantId,
            'product_id'         => $this->batch->productId,
            'batch_number'       => $this->batch->batchNumber,
            'manufacturing_date' => $this->batch->manufacturingDate?->format('Y-m-d'),
            'expiry_date'        => $this->batch->expiryDate?->format('Y-m-d'),
            'supplier_id'        => $this->batch->supplierId,
            'status'             => $this->batch->status,
            'attributes'         => $this->batch->attributes,
        ];
    }
}
