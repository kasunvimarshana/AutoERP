<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'assigned_to' => $this->assigned_to,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'source' => $this->source,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'company_name' => $this->company_name,
            'job_title' => $this->job_title,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'website' => $this->website,
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],
            'estimated_value' => $this->estimated_value,
            'probability' => $this->probability,
            'expected_close_date' => $this->expected_close_date?->toDateString(),
            'notes' => $this->notes,
            'metadata' => $this->metadata ?? [],
            'converted_to_customer_id' => $this->converted_to_customer_id,
            'converted_at' => $this->converted_at?->toISOString(),
            'is_converted' => $this->isConverted(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
