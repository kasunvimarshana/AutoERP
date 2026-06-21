<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalReservationResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'reservation_number' => $this->reservation_number,
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name'])),
            'requested_vehicle' => $this->whenLoaded('requestedVehicle', fn () => $this->summary($this->requestedVehicle, ['vehicle_number', 'registration_number', 'status'])),
            'requested_vehicle_category' => $this->whenLoaded('requestedVehicleCategory', fn () => $this->summary($this->requestedVehicleCategory, ['code', 'name'])),
            'rental_mode' => $this->enumValue($this->rental_mode),
            'billing_cycle' => $this->enumValue($this->billing_cycle),
            'requested_start_at' => $this->requested_start_at?->toISOString(),
            'requested_end_at' => $this->requested_end_at?->toISOString(),
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'name', 'symbol'])),
            'estimated_amount' => $this->decimal($this->estimated_amount),
            'estimated_deposit_amount' => $this->decimal($this->estimated_deposit_amount),
            'status' => $this->enumValue($this->status),
            'source' => $this->source,
            'remarks' => $this->remarks,
            'agreement' => $this->whenLoaded('agreement', fn () => $this->summary($this->agreement, ['agreement_number', 'agreement_kind', 'status'])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
