<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->getFullNameAttribute(),
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'designation' => $this->designation,
            'department' => $this->department,
            'is_primary' => $this->is_primary,
            'is_decision_maker' => $this->is_decision_maker,
            'email_opt_in' => $this->email_opt_in,
            'sms_opt_in' => $this->sms_opt_in,
            'phone_opt_in' => $this->phone_opt_in,
            'preferred_contact_method' => $this->preferred_contact_method,
            'preferred_contact_time' => $this->preferred_contact_time,
            'linkedin_url' => $this->linkedin_url,
            'twitter_handle' => $this->twitter_handle,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'birthday' => $this->birthday?->toDateString(),
            'custom_fields' => $this->custom_fields,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'customer' => $this->whenLoaded('customer'),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
        ];
    }
}
