<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleUseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'row_version' => $this->row_version, 'status' => $this->status->value,
            'customer_agreement' => ['id' => $this->customer_agreement_id, 'reference' => $this->customerAgreement->reference, 'party_name' => $this->customerAgreement->party_name_snapshot, 'version' => $this->customer_agreement_version],
            'owner_agreement' => $this->ownerAgreement === null ? null : ['id' => $this->owner_agreement_id, 'reference' => $this->ownerAgreement->reference, 'party_name' => $this->ownerAgreement->party_name_snapshot, 'version' => $this->owner_agreement_version],
            'vehicle' => ['id' => $this->vehicle_id, 'label' => $this->vehicle_label_snapshot],
            'replaces_use' => $this->replacesUse === null ? null : ['id' => $this->replacesUse->id, 'vehicle_label' => $this->replacesUse->vehicle_label_snapshot],
            'starts_at' => $this->starts_at_input, 'ends_at' => $this->ends_at_input,
            'handed_over_at' => $this->handed_over_at?->toIso8601String(), 'returned_at' => $this->returned_at?->toIso8601String(),
            'handover_odometer' => $this->handover_odometer, 'return_odometer' => $this->return_odometer, 'notes' => $this->notes];
    }
}
