<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalRateSnapshotResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'base_rate' => (string) $this->base_rate,
            'rate_unit' => $this->enum($this->rate_unit),
            'allowed_hours' => (string) $this->allowed_hours,
            'allowed_km' => (string) $this->allowed_km,
            'extra_hour_rate' => (string) $this->extra_hour_rate,
            'extra_km_rate' => (string) $this->extra_km_rate,
            'overtime_rate' => (string) $this->overtime_rate,
            'double_overtime_rate' => (string) $this->double_overtime_rate,
            'night_shift_rate' => (string) $this->night_shift_rate,
            'weekend_rate' => (string) $this->weekend_rate,
            'holiday_rate' => (string) $this->holiday_rate,
            'driver_rate' => (string) $this->driver_rate,
            'outstation_rate' => (string) $this->outstation_rate,
            'day_out_rate' => (string) $this->day_out_rate,
            'night_out_rate' => (string) $this->night_out_rate,
            'fuel_rate' => (string) $this->fuel_rate,
            'waiting_hour_rate' => (string) $this->waiting_hour_rate,
            'tax_profile_id' => $this->tax_profile_id,
            'currency_id' => $this->currency_id,
        ];
    }
}
