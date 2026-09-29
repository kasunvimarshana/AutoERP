<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AgreementHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $snapshot = $this->snapshot;

        return [
            'version' => $this->row_version,
            'action' => $this->action,
            'reason' => $this->reason,
            'recorded_at' => $this->recorded_at->toIso8601String(),
            'actor' => ['id' => $this->actor->getKey(), 'name' => trim($this->actor->first_name.' '.$this->actor->last_name)],
            'reference' => $snapshot['reference'],
            'party_name' => $snapshot['party_name_snapshot'],
            'currency_code' => $snapshot['currency_code_snapshot'],
            'status' => $snapshot['status'],
            'agreed_on' => $snapshot['agreed_on'],
            'executing_on' => $snapshot['executing_on'] ?? null,
            'starts_on' => $snapshot['starts_on'],
            'ends_on' => $snapshot['ends_on'],
            'basis' => $snapshot['basis'],
            'driver_mode' => $snapshot['driver_mode'],
            'terms' => $snapshot['terms'],
            'notes' => $snapshot['notes'],
        ];
    }
}
