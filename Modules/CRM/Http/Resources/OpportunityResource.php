<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $weightedValue = $this->amount && $this->probability
            ? bcmul((string) $this->amount, (string) ($this->probability / 100), 2)
            : null;

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'organization_id' => $this->organization_id,
            'opportunity_code' => $this->opportunity_code,
            'title' => $this->title,
            'stage' => $this->stage->value,
            'stage_label' => $this->stage->label(),
            'amount' => $this->amount,
            'probability' => $this->probability,
            'weighted_value' => $weightedValue,
            'expected_close_date' => $this->expected_close_date?->toDateString(),
            'actual_close_date' => $this->actual_close_date?->toDateString(),
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'description' => $this->description,
            'notes' => $this->notes,
            'metadata' => $this->metadata ?? [],
            'is_won' => $this->stage->isWon(),
            'is_lost' => $this->stage->isLost(),
            'is_open' => $this->stage->isOpen(),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
