<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lead Resource
 *
 * Transforms lead data for API responses.
 */
class LeadResource extends JsonResource
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
            'lead_number' => $this->lead_number,
            'customer_id' => $this->customer_id,
            'source' => $this->source,
            'title' => $this->title,
            'description' => $this->description,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'company' => $this->company,
            'status' => $this->status,
            'stage' => $this->stage,
            'probability' => $this->probability,
            'expected_revenue' => $this->expected_revenue,
            'expected_close_date' => $this->expected_close_date?->toIso8601String(),
            'assigned_to' => $this->assigned_to,
            'tags' => $this->tags,
            'notes' => $this->notes,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'assignee' => $this->whenLoaded('assignee'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
