<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Modules\VehicleRental\Constants\RunningChartFields;

final class RunningChartHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $facts = Arr::only($this->snapshot, RunningChartFields::MUTABLE);
        $facts['starts_at'] = $this->snapshot['starts_at_input'];
        $facts['ends_at'] = $this->snapshot['ends_at_input'];

        return ['version' => $this->row_version, 'action' => $this->action, 'reason' => $this->reason, 'recorded_at' => $this->recorded_at->toIso8601String(), 'actor' => ['name' => trim($this->actor->first_name.' '.$this->actor->last_name)], 'facts' => $facts];
    }
}
