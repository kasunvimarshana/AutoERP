<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleUseHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $s = $this->snapshot;

        return ['version' => $this->row_version, 'action' => $this->action, 'reason' => $this->reason, 'recorded_at' => $this->recorded_at->toIso8601String(),
            'actor' => ['name' => trim($this->actor->first_name.' '.$this->actor->last_name)], 'vehicle_label' => $s['vehicle_label_snapshot'],
            'status' => $s['status'], 'starts_at' => $s['starts_at_input'], 'ends_at' => $s['ends_at_input'],
            'handed_over_at' => ($s['handed_over_at'] ?? null), 'returned_at' => ($s['returned_at'] ?? null), 'handover_odometer' => ($s['handover_odometer'] ?? null), 'return_odometer' => ($s['return_odometer'] ?? null)];
    }
}
