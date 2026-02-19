<?php

declare(strict_types=1);

namespace Modules\Customer\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer Resource
 *
 * Transforms Customer model data for API responses
 */
class CustomerResource extends JsonResource
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
            'customer_number' => $this->customer_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'address' => [
                'line_1' => $this->address_line_1,
                'line_2' => $this->address_line_2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],
            'notes' => $this->notes,
            'status' => $this->status,
            'customer_type' => $this->customer_type,
            'company_name' => $this->company_name,
            'tax_id' => $this->tax_id,
            'preferences' => [
                'receive_notifications' => $this->receive_notifications,
                'receive_marketing' => $this->receive_marketing,
            ],
            'last_service_date' => $this->last_service_date?->format('Y-m-d H:i:s'),
            'vehicles_count' => $this->whenCounted('vehicles'),
            'vehicles' => VehicleResource::collection($this->whenLoaded('vehicles')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
