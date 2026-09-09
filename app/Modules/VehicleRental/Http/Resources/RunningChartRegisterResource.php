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
            'customer_agreement' => ['reference' => $use->customerAgreement->reference, 'party_name' => $use->customerAgreement->party_name_snapshot],
            'owner_agreement' => $use->ownerAgreement === null ? null : ['reference' => $use->ownerAgreement->reference, 'party_name' => $use->ownerAgreement->party_name_snapshot],
            'replaces_vehicle' => $use->replacesUse?->vehicle_label_snapshot,
        ]);
    }
}
