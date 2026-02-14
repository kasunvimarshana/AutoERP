<?php

namespace App\Modules\CRM\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer Resource
 * 
 * Transforms customer model data for API responses
 * 
 * @package App\Modules\CRM\Resources
 */
class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'status' => $this->status,
            'notes' => $this->notes,
            'contacts' => $this->whenLoaded('contacts', function () {
                return ContactResource::collection($this->contacts);
            }),
            'vehicles' => $this->whenLoaded('vehicles', function () {
                return VehicleResource::collection($this->vehicles);
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
