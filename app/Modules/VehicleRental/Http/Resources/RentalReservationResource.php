<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Modules\VehicleRental\Enums\RentalPartyType;

final class RentalReservationResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        $party = $this->party_type === RentalPartyType::Customer ? $this->customer : $this->supplier;

        return [
            'id' => (int) $this->getKey(),
            'reservation_number' => $this->reservation_number,
            'direction' => $this->enum($this->direction),
            'party_type' => $this->enum($this->party_type),
            'party_id' => (int) $this->party_id,
            'party' => $this->partySummary($party),
            'rental_type' => $this->enum($this->rental_type),
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->vehicleSummary($this->vehicle)),
            'start_at' => $this->start_at?->toISOString(),
            'expected_end_at' => $this->expected_end_at?->toISOString(),
            'currency_id' => $this->currency_id,
            'status' => $this->enum($this->status),
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
