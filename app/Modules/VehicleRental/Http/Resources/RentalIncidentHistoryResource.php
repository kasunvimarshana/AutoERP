<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RentalIncidentHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'action' => $this->action,
            'reason' => $this->reason,
            'recorded_at' => $this->recorded_at->toIso8601String(),
            'actor' => ['name' => trim($this->actor->first_name.' '.$this->actor->last_name)],
        ];
    }
}
