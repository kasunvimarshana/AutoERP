<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RunningChartRegisterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $use = $this->vehicleUse;

        return array_merge((new RunningChartResource($this->resource))->toArray($request), [
            'customer_agreement' => $use->customerAgreement === null ? null : [
                'id' => $use->customer_agreement_id,
                'reference' => $use->customerAgreement->reference,
                'party_name' => $use->customerAgreement->party_name_snapshot,
                'version' => $use->customer_agreement_version,
                'driver_mode' => $use->customerAgreement->driver_mode?->value,
                'working_hours' => $use->customerAgreement->terms['working_hours'] ?? null,
            ],
            'owner_agreement' => $use->ownerAgreement === null ? null : [
                'id' => $use->owner_agreement_id,
                'reference' => $use->ownerAgreement->reference,
                'party_name' => $use->ownerAgreement->party_name_snapshot,
                'version' => $use->owner_agreement_version,
                'driver_mode' => $use->ownerAgreement->driver_mode?->value,
                'working_hours' => $use->ownerAgreement->terms['working_hours'] ?? null,
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
                'customer_agreement' => $use->customerAgreement === null ? null : [
                    'id' => $use->customer_agreement_id,
                    'reference' => $use->customerAgreement->reference,
                    'party_name' => $use->customerAgreement->party_name_snapshot,
                    'version' => $use->customer_agreement_version,
                    'driver_mode' => $use->customerAgreement->driver_mode?->value,
                    'working_hours' => $use->customerAgreement->terms['working_hours'] ?? null,
                ],
                'owner_agreement' => $use->ownerAgreement === null ? null : [
                    'id' => $use->owner_agreement_id,
                    'reference' => $use->ownerAgreement->reference,
                    'party_name' => $use->ownerAgreement->party_name_snapshot,
                    'version' => $use->owner_agreement_version,
                    'driver_mode' => $use->ownerAgreement->driver_mode?->value,
                    'working_hours' => $use->ownerAgreement->terms['working_hours'] ?? null,
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
