<?php

declare(strict_types=1);

namespace Modules\Wms\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wms\Domain\Entities\CycleCountLine;

class CycleCountLineResource extends JsonResource
{
    /** @var CycleCountLine */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'cycle_count_id' => $this->resource->cycleCountId,
            'tenant_id' => $this->resource->tenantId,
            'product_id' => $this->resource->productId,
            'bin_id' => $this->resource->binId,
            'system_qty' => $this->resource->systemQty,
            'counted_qty' => $this->resource->countedQty,
            'variance' => $this->resource->variance,
            'notes' => $this->resource->notes,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
