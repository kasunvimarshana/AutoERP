<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalAgreementResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'agreement_number' => $this->agreement_number,
            'agreement_kind' => $this->enumValue($this->agreement_kind),
            'reservation' => $this->whenLoaded('reservation', fn () => $this->summary($this->reservation, ['reservation_number', 'status', 'requested_start_at', 'requested_end_at'])),
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name'])),
            'supplier' => $this->whenLoaded('supplier', fn () => $this->summary($this->supplier, ['supplier_number', 'code', 'name', 'display_name'])),
            'agreement_date' => $this->agreement_date?->toDateString(),
            'executed_at' => $this->executed_at?->toISOString(),
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'actual_ended_at' => $this->actual_ended_at?->toISOString(),
            'legal_context' => $this->legal_context,
            'rental_mode' => $this->enumValue($this->rental_mode),
            'billing_cycle' => $this->enumValue($this->billing_cycle),
            'billing_basis' => $this->enumValue($this->billing_basis),
            'proration_rule' => $this->enumValue($this->proration_rule),
            'billing_timezone' => $this->billing_timezone,
            'payment_term_days' => $this->payment_term_days,
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'name', 'symbol'])),
            'status' => $this->enumValue($this->status),
            'termination_reason' => $this->termination_reason,
            'remarks' => $this->remarks,
            'document_snapshot' => $this->resource->relationLoaded('terms')
                ? data_get($this->metadata, 'document_snapshot')
                : null,
            'terms' => $this->resource->relationLoaded('terms')
                ? $this->resource->getRelation('terms')
                    ->where('is_active', true)
                    ->map(fn ($term): array => [
                        'id' => (int) $term->getKey(),
                        'row_version' => (int) $term->row_version,
                        'sequence' => (int) $term->sequence,
                        'term_code' => $term->term_code,
                        'title' => $term->title,
                        'content' => $term->content,
                        'is_printable' => (bool) $term->is_printable,
                        'is_active' => (bool) $term->is_active,
                    ])->values()->all()
                : [],
            'active_rate_version' => $this->whenLoaded('activeRateVersion', fn () => $this->activeRateVersion === null
                ? null
                : (new RentalRateVersionResource($this->activeRateVersion))->resolve($request)),
            'rate_versions' => $this->whenLoaded('rateVersions', fn () => RentalRateVersionResource::collection($this->rateVersions)->resolve($request), []),
            'allocations' => $this->whenLoaded('allocations', fn () => RentalAllocationResource::collection($this->allocations)->resolve($request), []),
            'deposit_requirement' => $this->whenLoaded('depositRequirement', fn () => $this->depositRequirement === null
                ? null
                : (new RentalDepositRequirementResource($this->depositRequirement))->resolve($request)),
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
