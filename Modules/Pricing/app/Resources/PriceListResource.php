<?php

declare(strict_types=1);

namespace Modules\Pricing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PriceList Resource
 *
 * Transforms PriceList model for API responses
 */
class PriceListResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status,
            'currency_code' => $this->currency_code,
            'is_default' => $this->is_default,
            'priority' => $this->priority,
            'customer_id' => $this->customer_id,
            'location_code' => $this->location_code,
            'customer_group' => $this->customer_group,
            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'is_valid_now' => $this->isValidNow(),
            'is_active' => $this->isActive(),

            // Relationships
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ]),
            'items' => PriceListItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
