<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Modules\VehicleRental\Enums\RentalPartyType;

final class RentalAgreementResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        $party = $this->party_type === RentalPartyType::Customer ? $this->customer : $this->supplier;

        return [
            'id' => (int) $this->getKey(),
            'agreement_number' => $this->agreement_number,
            'reservation_id' => $this->reservation_id,
            'direction' => $this->enum($this->direction),
            'party_type' => $this->enum($this->party_type),
            'party_id' => (int) $this->party_id,
            'party' => $this->partySummary($party),
            'rental_type' => $this->enum($this->rental_type),
            'billing_cycle' => $this->enum($this->billing_cycle),
            'agreement_date' => $this->agreement_date?->toDateString(),
            'start_at' => $this->start_at?->toISOString(),
            'expected_end_at' => $this->expected_end_at?->toISOString(),
            'actual_end_at' => $this->actual_end_at?->toISOString(),
            'currency_id' => $this->currency_id,
            'status' => $this->enum($this->status),
            'status_label' => str((string) $this->enum($this->status))->replace('_', ' ')->title()->toString(),
            'terms_snapshot' => $this->terms_snapshot,
            'remarks' => $this->remarks,
            'rate_snapshot' => $this->whenLoaded('rateSnapshot', fn () => $this->rateSnapshot === null
                ? null
                : (new RentalRateSnapshotResource($this->rateSnapshot))->resolve($request)),
            'vehicles' => $this->whenLoaded('vehicles', fn () => RentalAgreementVehicleResource::collection($this->vehicles)->resolve($request), []),
            'usage_logs' => $this->whenLoaded('usageLogs', fn () => RentalUsageLogResource::collection($this->usageLogs)->resolve($request), []),
            'expenses' => $this->whenLoaded('expenses', fn () => RentalExpenseResource::collection($this->expenses)->resolve($request), []),
            'charges' => $this->whenLoaded('charges', fn () => RentalChargeResource::collection($this->charges)->resolve($request), []),
            'invoice_links' => $this->whenLoaded('invoiceLinks', fn () => RentalInvoiceLinkResource::collection($this->invoiceLinks)->resolve($request), []),
            'payment_links' => $this->whenLoaded('paymentLinks', fn () => RentalPaymentLinkResource::collection($this->paymentLinks)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
