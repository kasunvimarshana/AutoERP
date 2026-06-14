<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalUsageLogResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'agreement_vehicle_id' => (int) $this->agreement_vehicle_id,
            'vehicle_id' => (int) $this->vehicle_id,
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->vehicleSummary($this->vehicle)),
            'driver_id' => $this->driver_id,
            'driver' => $this->whenLoaded('driver', fn () => $this->driver === null ? null : [
                'id' => (int) $this->driver->getKey(),
                'code' => $this->driver->employee_number,
                'name' => $this->driver->display_name,
            ]),
            'usage_date' => $this->usage_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'working_minutes' => (int) $this->working_minutes,
            'start_odometer' => (string) $this->start_odometer,
            'end_odometer' => (string) $this->end_odometer,
            'distance_km' => (string) $this->distance_km,
            'cumulative_km' => $this->cumulative_km === null ? null : (string) $this->cumulative_km,
            'comparative_km' => $this->comparative_km === null ? null : (string) $this->comparative_km,
            'trip_from' => $this->trip_from,
            'trip_to' => $this->trip_to,
            'trip_purpose' => $this->trip_purpose,
            'status' => $this->enum($this->status),
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'remarks' => $this->remarks,
            'events' => $this->whenLoaded('events', fn () => RentalUsageEventResource::collection($this->events)->resolve($request), []),
            'contexts' => $this->whenLoaded('contexts', fn () => RentalUsageContextResource::collection($this->contexts)->resolve($request), []),
        ];
    }
}
