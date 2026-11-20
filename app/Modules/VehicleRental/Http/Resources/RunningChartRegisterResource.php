<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RunningChartRegisterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $use = $this->vehicleUse;

        return array_merge((new RunningChartResource($this->resource))->toArray($request), [
            'customer_agreement' => [
                'id' => $use->customer_agreement_id,
                'reference' => $use->customerAgreement->reference,
                'party_name' => $use->customerAgreement->party_name_snapshot,
                'driver_mode' => $use->customerAgreement->driver_mode?->value,
            ],
            'owner_agreement' => $use->ownerAgreement === null ? null : [
                'id' => $use->owner_agreement_id,
                'reference' => $use->ownerAgreement->reference,
                'party_name' => $use->ownerAgreement->party_name_snapshot,
            ],
            'replaces_vehicle' => $use->replacesUse?->vehicle_label_snapshot,
            'vehicle_use' => [
                'id' => $use->id,
                'version' => $use->row_version,
                'row_version' => $use->row_version,
                'chart_use_version' => $this->vehicle_use_version,
                'status' => $use->status->value,
                'vehicle_label' => $use->vehicle_label_snapshot,
                'starts_at' => $use->starts_at->toIso8601String(),
                'ends_at' => $use->ends_at?->toIso8601String(),
                'handed_over_at' => $use->handed_over_at?->toIso8601String(),
                'returned_at' => $use->returned_at?->toIso8601String(),
                'handover_odometer' => $use->handover_odometer,
                'return_odometer' => $use->return_odometer,
                'notes' => $use->notes,
                'customer_agreement' => [
                    'id' => $use->customer_agreement_id,
                    'reference' => $use->customerAgreement->reference,
                    'party_name' => $use->customerAgreement->party_name_snapshot,
                    'driver_mode' => $use->customerAgreement->driver_mode?->value,
                ],
                'owner_agreement' => $use->ownerAgreement === null ? null : [
                    'id' => $use->owner_agreement_id,
                    'reference' => $use->ownerAgreement->reference,
                    'party_name' => $use->ownerAgreement->party_name_snapshot,
                ],
                'vehicle' => [
                    'id' => $use->vehicle_id,
                    'label' => $use->vehicle_label_snapshot,
                ],
                'replaces_use' => $use->replacesUse === null ? null : [
                    'id' => $use->replacesUse->id,
                    'vehicle_label' => $use->replacesUse->vehicle_label_snapshot,
                ],
                'replaced_by_use' => $use->relationLoaded('replacedByUse') && $use->replacedByUse !== null ? [
                    'id' => $use->replacedByUse->id,
                    'vehicle_label' => $use->replacedByUse->vehicle_label_snapshot,
                ] : null,
            ],
        ]);
    }
}
