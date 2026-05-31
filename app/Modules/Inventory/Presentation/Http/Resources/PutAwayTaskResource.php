<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Application\DTO\DataRecord;

final class PutAwayTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DataRecord) {
            return InventoryResourceLabels::enrich($this->resource->toArray());
        }

        if (is_array($this->resource)) {
            return InventoryResourceLabels::enrich($this->resource);
        }

        return [];
    }
}
