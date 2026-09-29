<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\VehicleRental\Constants\RunningChartFields;

final class RunningChartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $facts = $this->resource->only(RunningChartFields::MUTABLE);
        $facts['driver_identity_source'] = $this->driver_identity_source?->value;

        return array_merge($facts, [
            'id' => $this->id,
            'row_version' => $this->row_version,
            'status' => $this->status->value,
            'starts_at' => $this->starts_at_input,
            'ends_at' => $this->ends_at_input,
            'total_km' => $this->totalKm(),
            'driver' => $this->driver_identity_source === null ? null : [
                'source' => $this->driver_identity_source->value,
                'employee_id' => $this->driver_employee_id,
                'name' => $this->driver_name_snapshot,
                'reference' => $this->driver_reference_snapshot,
            ],
            'vehicle_use' => ['id' => $this->vehicleUse->id, 'vehicle_label' => $this->vehicleUse->vehicle_label_snapshot, 'version' => $this->vehicle_use_version],
            'corrects_chart' => $this->correctsChart === null ? null : ['id' => $this->correctsChart->id, 'reference' => $this->correctsChart->reference],
            'finalized_at' => $this->finalized_at?->toIso8601String(),
            'reversed_at' => $this->reversed_at?->toIso8601String(),
        ]);
    }
}
