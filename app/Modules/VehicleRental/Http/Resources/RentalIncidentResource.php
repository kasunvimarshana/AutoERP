<?php
declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RentalIncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'reference' => $this->reference,
            'row_version' => (int) $this->row_version,
            'incident_type' => $this->incident_type->value,
            'status' => $this->status->value,
            'occurred_on' => $this->occurred_on?->toDateString(),
            'evidence_reference' => $this->evidence_reference,
            'description' => $this->description,
            'vehicle_use_id' => (int) $this->vehicle_use_id,
            'vehicle_use_version' => (int) $this->vehicle_use_version,
            'running_chart_id' => $this->running_chart_id === null ? null : (int) $this->running_chart_id,
            'vehicle' => $this->whenLoaded('vehicleUse', fn () => [
                'label' => $this->vehicleUse->vehicle_label_snapshot,
                'customer_agreement' => $this->vehicleUse->customerAgreement?->reference,
                'owner_agreement' => $this->vehicleUse->ownerAgreement?->reference,
            ]),
            'running_chart' => $this->whenLoaded('runningChart', fn () => $this->runningChart?->reference),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
        ];
    }
}
