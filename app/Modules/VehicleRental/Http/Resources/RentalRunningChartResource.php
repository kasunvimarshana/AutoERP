<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RentalRunningChartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'chart_number' => $this->chart_number,
            'status' => $this->enum($this->status),
            'operational_date' => $this->operational_date?->toDateString(),
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'assignment' => $this->whenLoaded('assignment', fn () => [
                'id' => (int) $this->assignment->getKey(),
                'agreement' => $this->assignment->relationLoaded('agreement') ? [
                    'id' => (int) $this->assignment->agreement->getKey(),
                    'code' => $this->assignment->agreement->agreement_number,
                    'name' => $this->assignment->agreement->agreement_number,
                ] : null,
                'vehicle' => $this->assignment->relationLoaded('vehicle') && $this->assignment->vehicle !== null ? [
                    'id' => (int) $this->assignment->vehicle->getKey(),
                    'code' => $this->assignment->vehicle->vehicle_number,
                    'name' => trim(
                        ($this->assignment->vehicle->registration_number ?? $this->assignment->vehicle->vehicle_number)
                        .' '.($this->assignment->vehicle->model?->name ?? ''),
                    ),
                    'odometer_reading' => $this->assignment->vehicle->odometer_reading === null
                        ? null
                        : (string) $this->assignment->vehicle->odometer_reading,
                ] : null,
                'owner_agreement' => $this->assignment->relationLoaded('sourceAssignment')
                    && $this->assignment->sourceAssignment?->relationLoaded('agreement') ? [
                    'id' => (int) $this->assignment->sourceAssignment->agreement->getKey(),
                    'code' => $this->assignment->sourceAssignment->agreement->agreement_number,
                    'name' => $this->assignment->sourceAssignment->agreement->agreement_number,
                ] : null,
            ]),
            'driver' => $this->whenLoaded('driver', fn () => $this->driver === null ? null : [
                'id' => (int) $this->driver->getKey(),
                'code' => $this->driver->employee_number,
                'name' => $this->driver->display_name,
            ]),
            'ac_mode' => $this->enum($this->ac_mode),
            'start_odometer' => $this->decimalOrNull($this->start_odometer),
            'end_odometer' => $this->decimalOrNull($this->end_odometer),
            'total_km' => $this->decimalOrNull($this->total_km),
            'garage_km' => $this->decimalOrNull($this->garage_km),
            'commercial_km' => $this->decimalOrNull($this->commercial_km),
            'normal_overtime_hours' => (string) $this->normal_overtime_hours,
            'double_overtime_hours' => (string) $this->double_overtime_hours,
            'triple_overtime_hours' => (string) $this->triple_overtime_hours,
            'night_out_count' => (int) $this->night_out_count,
            'trip_origin' => $this->trip_origin,
            'trip_destination' => $this->trip_destination,
            'purpose' => $this->purpose,
            'odometer_variance_reason' => $this->odometer_variance_reason,
            'remarks' => $this->remarks,
            'replaces_running_chart' => $this->whenLoaded('replacesRunningChart', fn () => $this->replacesRunningChart === null ? null : [
                'id' => (int) $this->replacesRunningChart->getKey(),
                'chart_number' => $this->replacesRunningChart->chart_number,
            ]),
            'finalized_at' => $this->finalized_at?->toISOString(),
            'reversed_at' => $this->reversed_at?->toISOString(),
            'reversal_reason' => $this->reversal_reason,
        ];
    }

    private function decimalOrNull(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
