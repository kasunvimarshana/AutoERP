<?php
declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class IncidentVehicleUseOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'row_version' => (int) $this->row_version,
            'vehicle_label' => $this->vehicle_label_snapshot,
            'customer_agreement_reference' => $this->customerAgreement?->reference,
            'customer_party_name' => $this->customerAgreement?->party_name_snapshot,
        ];
    }
}
