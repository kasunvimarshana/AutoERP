<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Modules\VehicleRental\Enums\RentalPartyType;

final class RentalAgreementVehicleLinkResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        $inboundParty = $this->inboundAgreement?->party_type === RentalPartyType::Customer
            ? $this->inboundAgreement?->customer
            : $this->inboundAgreement?->supplier;
        $outboundParty = $this->outboundAgreement?->party_type === RentalPartyType::Customer
            ? $this->outboundAgreement?->customer
            : $this->outboundAgreement?->supplier;

        return [
            'id' => (int) $this->getKey(),
            'vehicle_id' => (int) $this->vehicle_id,
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->vehicleSummary($this->vehicle)),
            'inbound_agreement_id' => (int) $this->inbound_agreement_id,
            'inbound_agreement_vehicle_id' => (int) $this->inbound_agreement_vehicle_id,
            'inbound_agreement' => $this->whenLoaded('inboundAgreement', fn () => [
                'id' => (int) $this->inboundAgreement->getKey(),
                'agreement_number' => $this->inboundAgreement->agreement_number,
                'direction' => $this->enum($this->inboundAgreement->direction),
                'party' => $this->partySummary($inboundParty),
            ]),
            'outbound_agreement_id' => (int) $this->outbound_agreement_id,
            'outbound_agreement_vehicle_id' => (int) $this->outbound_agreement_vehicle_id,
            'outbound_agreement' => $this->whenLoaded('outboundAgreement', fn () => [
                'id' => (int) $this->outboundAgreement->getKey(),
                'agreement_number' => $this->outboundAgreement->agreement_number,
                'direction' => $this->enum($this->outboundAgreement->direction),
                'party' => $this->partySummary($outboundParty),
            ]),
            'effective_from' => $this->effective_from?->toISOString(),
            'effective_to' => $this->effective_to?->toISOString(),
            'status' => $this->enum($this->status),
            'remarks' => $this->remarks,
            'submitted_by' => $this->submitted_by,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'cancelled_by' => $this->cancelled_by,
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'superseded_by_link_id' => $this->superseded_by_link_id,
        ];
    }
}
