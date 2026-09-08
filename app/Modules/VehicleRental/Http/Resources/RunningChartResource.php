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
        return array_merge($this->resource->only(RunningChartFields::MUTABLE), ['id' => $this->id, 'row_version' => $this->row_version, 'status' => $this->status->value, 'starts_at' => $this->starts_at_input, 'ends_at' => $this->ends_at_input, 'total_km' => $this->totalKm(), 'vehicle_use' => ['id' => $this->vehicleUse->id, 'vehicle_label' => $this->vehicleUse->vehicle_label_snapshot, 'version' => $this->vehicle_use_version], 'corrects_chart' => $this->correctsChart === null ? null : ['id' => $this->correctsChart->id, 'reference' => $this->correctsChart->reference], 'finalized_at' => $this->finalized_at?->toIso8601String(), 'reversed_at' => $this->reversed_at?->toIso8601String()]);
    }
}
