<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\VehicleRental\Models\OwnerAgreement;

final class AgreementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'reference' => $this->reference, 'row_version' => $this->row_version,
            'status' => $this->status->value, 'basis' => $this->basis->value, 'driver_mode' => $this->driver_mode->value,
            'party' => ['id' => $this->party->getKey(), 'name' => $this->party_name_snapshot, 'code' => $this->party_code_snapshot],
            'currency' => ['id' => $this->currency->getKey(), 'code' => $this->currency_code_snapshot, 'name' => $this->currency->name],
            'vehicle' => $this->when($this->resource instanceof OwnerAgreement, fn () => ['id' => $this->vehicle->getKey(), 'registration_number' => $this->vehicle_registration_snapshot, 'vehicle_number' => $this->vehicle_number_snapshot]),
            'agreed_on' => $this->agreed_on->toDateString(), 'starts_on' => $this->starts_on->toDateString(), 'ends_on' => $this->ends_on?->toDateString(),
            'terms' => $this->terms, 'notes' => $this->notes, 'activated_at' => $this->activated_at?->toIso8601String(), 'closed_at' => $this->closed_at?->toIso8601String()];
    }
}
