<?php

declare(strict_types=1);

namespace Modules\JobCard\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * InspectionItem Resource
 *
 * Transforms InspectionItem model data for API responses
 */
class InspectionItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_card_id' => $this->job_card_id,
            'item_type' => $this->item_type,
            'item_name' => $this->item_name,
            'condition' => $this->condition,
            'notes' => $this->notes,
            'photos' => $this->photos,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
