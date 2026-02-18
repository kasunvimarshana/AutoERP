<?php

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_code' => $this->code,
            'facility_name' => $this->name,
            'facility_description' => $this->description,
            'location_details' => $this->buildLocationDetails(),
            'contact_information' => $this->buildContactInfo(),
            'facility_manager' => $this->when(
                $this->relationLoaded('manager'),
                fn() => [
                    'manager_id' => $this->manager_id,
                    'manager_name' => $this->manager?->name,
                ]
            ),
            'operational_status' => $this->is_active ? 'operational' : 'inactive',
            'storage_locations' => $this->when(
                $this->relationLoaded('locations'),
                fn() => [
                    'total_locations' => $this->locations->count(),
                    'location_details' => WarehouseLocationResource::collection($this->locations),
                ]
            ),
            'inventory_summary' => $this->when(
                $this->relationLoaded('stockLedgers'),
                fn() => [
                    'unique_products' => $this->stockLedgers->pluck('product_id')->unique()->count(),
                    'total_quantity' => $this->stockLedgers->sum('quantity'),
                ]
            ),
            'timestamps' => [
                'established' => $this->created_at?->toIso8601String(),
                'last_modified' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }

    private function buildLocationDetails(): array
    {
        return array_filter([
            'street_address' => $this->address,
            'city' => $this->city,
            'state_province' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'formatted_address' => $this->getFormattedAddress(),
        ]);
    }

    private function buildContactInfo(): array
    {
        return array_filter([
            'phone_number' => $this->phone,
            'email_address' => $this->email,
        ]);
    }

    private function getFormattedAddress(): ?string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }
}
