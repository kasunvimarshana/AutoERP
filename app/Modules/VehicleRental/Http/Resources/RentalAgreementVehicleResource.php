<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalAgreementVehicleResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'vehicle_id' => (int) $this->vehicle_id,
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->vehicleSummary($this->vehicle)),
            'owner_party_type' => $this->enum($this->owner_party_type),
            'owner_party_id' => $this->owner_party_id,
            'allocated_from' => $this->allocated_from?->toISOString(),
            'allocated_to' => $this->allocated_to?->toISOString(),
            'start_odometer' => (string) $this->start_odometer,
            'end_odometer' => $this->end_odometer === null ? null : (string) $this->end_odometer,
            'status' => $this->enum($this->status),
            'remarks' => $this->remarks,
            'pickup_inspection' => $this->whenLoaded('pickupInspection', fn () => $this->pickupInspection === null
                ? null
                : (new RentalInspectionResource($this->pickupInspection))->resolve($request)),
            'return_inspection' => $this->whenLoaded('returnInspection', fn () => $this->returnInspection === null
                ? null
                : (new RentalInspectionResource($this->returnInspection))->resolve($request)),
        ];
    }
}
