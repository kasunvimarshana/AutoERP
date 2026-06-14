<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Modules\VehicleRental\Enums\RentalPartyType;

final class RentalUsageContextResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        $agreement = $this->agreement;
        $party = $agreement?->party_type === RentalPartyType::Customer
            ? $agreement?->customer
            : $agreement?->supplier;

        return [
            'id' => (int) $this->getKey(),
            'agreement_id' => (int) $this->agreement_id,
            'agreement_vehicle_id' => (int) $this->agreement_vehicle_id,
            'agreement_vehicle_link_id' => $this->agreement_vehicle_link_id,
            'agreement_number' => $agreement?->agreement_number,
            'agreement_direction' => $this->enum($this->agreement_direction),
            'financial_side' => $this->enum($this->financial_side),
            'party_type' => $this->enum($this->party_type),
            'party_id' => (int) $this->party_id,
            'party' => $this->partySummary($party),
            'rate_snapshot' => $this->whenLoaded('rateSnapshot', fn () => $this->rateSnapshot === null
                ? null
                : (new RentalRateSnapshotResource($this->rateSnapshot))->resolve($request)),
        ];
    }
}
