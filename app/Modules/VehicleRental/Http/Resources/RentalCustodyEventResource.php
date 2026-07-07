<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalCustodyEventResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'event_number' => $this->event_number,
            'allocation' => $this->whenLoaded('allocation', fn () => $this->summary($this->allocation, ['allocation_number', 'status'])),
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->summary($this->vehicle, ['vehicle_number', 'registration_number', 'status'])),
            'replacement' => $this->whenLoaded('replacement', fn () => $this->summary($this->replacement, ['replacement_number', 'status', 'replacement_at'])),
            'event_type' => $this->enumValue($this->event_type),
            'occurred_at' => $this->occurred_at?->toISOString(),
            'odometer' => $this->decimal($this->odometer),
            'fuel_level_percent' => $this->fuel_level_percent === null ? null : $this->decimal($this->fuel_level_percent),
            'location' => $this->location,
            'from_role' => $this->from_role,
            'to_role' => $this->to_role,
            'handed_over_by_employee' => $this->whenLoaded('handedOverByEmployee', fn () => $this->summary($this->handedOverByEmployee, ['employee_number', 'first_name', 'last_name', 'display_name'])),
            'received_by_employee' => $this->whenLoaded('receivedByEmployee', fn () => $this->summary($this->receivedByEmployee, ['employee_number', 'first_name', 'last_name', 'display_name'])),
            'external_handed_over_name' => $this->external_handed_over_name,
            'external_received_by_name' => $this->external_received_by_name,
            'condition_summary' => $this->condition_summary,
            'damage_summary' => $this->damage_summary,
            'status' => $this->enumValue($this->status),
            'items' => $this->loadedCollection('items', fn ($item): array => [
                'id' => (int) $item->getKey(), 'sequence' => (int) $item->sequence,
                'item_type' => $this->enumValue($item->item_type), 'item_code' => $item->item_code,
                'description' => $item->description, 'expected_quantity' => $item->expected_quantity,
                'actual_quantity' => $item->actual_quantity, 'condition_status' => $item->condition_status,
                'is_chargeable' => (bool) $item->is_chargeable, 'estimated_amount' => $this->decimal($item->estimated_amount),
                'responsible_side' => $item->responsible_side, 'remarks' => $item->remarks,
            ]),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'reversed_at' => $this->reversed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
