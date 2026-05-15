<?php

namespace Modules\Document\Presentation\API\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Document\Domain\Entities\DocumentItem;

class DocumentItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DocumentItem) {
            return [
                'id' => $this->resource->id,
                'item_type' => $this->resource->itemType,
                'description' => $this->resource->description,
                'line_total' => $this->resource->lineTotal,
                'sequence' => $this->resource->sequence,
                'data' => $this->resource->data,
            ];
        }

        return [
            'id' => $this->id,
            'item_type' => $this->item_type,
            'description' => $this->description,
            'line_total' => $this->line_total,
            'sequence' => $this->sequence,
            'data' => $this->data ?? [],
        ];
    }
}
