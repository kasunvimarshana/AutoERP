<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'code' => $this->code,
            'title' => $this->title,
            'type' => $this->type,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->getFullNameAttribute(),
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'company_name' => $this->company_name,
            'designation' => $this->designation,
            'industry' => $this->industry,
            'company_size' => $this->company_size,
            'website' => $this->website,
            'source' => $this->source,
            'source_details' => $this->source_details,
            'status' => $this->status,
            'priority' => $this->priority,
            'rating' => $this->rating,
            'estimated_value' => $this->estimated_value,
            'currency_id' => $this->currency_id,
            'probability' => $this->probability,
            'expected_close_date' => $this->expected_close_date?->toDateString(),
            'assigned_to' => $this->assigned_to,
            'stage' => $this->stage,
            'is_converted' => $this->is_converted,
            'converted_customer_id' => $this->converted_customer_id,
            'converted_at' => $this->converted_at?->toIso8601String(),
            'converted_by' => $this->converted_by,
            'description' => $this->description,
            'notes' => $this->notes,
            'tags' => $this->tags,
            'custom_fields' => $this->custom_fields,
            'metadata' => $this->metadata,
            'expected_revenue' => $this->getExpectedRevenue(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'organization' => $this->whenLoaded('organization'),
            'branch' => $this->whenLoaded('branch'),
            'currency' => $this->whenLoaded('currency'),
            'assigned_user' => $this->whenLoaded('assignedUser', function () {
                return [
                    'id' => $this->assignedUser->id,
                    'name' => $this->assignedUser->name,
                    'email' => $this->assignedUser->email,
                ];
            }),
            'converted_customer' => $this->whenLoaded('convertedCustomer'),
            'converter' => $this->whenLoaded('converter', function () {
                return [
                    'id' => $this->converter->id,
                    'name' => $this->converter->name,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
        ];
    }
}
