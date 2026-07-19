<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RentalAgreementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'agreement_number' => $this->agreement_number,
            'kind' => $this->enum($this->kind),
            'customer' => $this->whenLoaded('customer', fn () => $this->party($this->customer)),
            'supplier' => $this->whenLoaded('supplier', fn () => $this->party($this->supplier)),
            'executed_at' => $this->executed_at?->toDateString(),
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'billing_basis' => $this->enum($this->billing_basis),
            'currency' => $this->whenLoaded('currency', fn () => $this->named($this->currency)),
            'tax_group' => $this->whenLoaded('taxGroup', fn () => $this->named($this->taxGroup)),
            'included_km' => (string) $this->included_km,
            'deposit_required' => (bool) $this->deposit_required,
            'deposit_amount' => (string) $this->deposit_amount,
            'payment_terms_days' => (int) $this->payment_terms_days,
            'status' => $this->enum($this->status),
            'terms' => $this->terms,
            'notes' => $this->notes,
            'rate_versions' => $this->whenLoaded('rateVersions', fn () => $this->rateVersions->map(fn ($version) => [
                'id' => (int) $version->getKey(),
                'row_version' => (int) $version->row_version,
                'version_number' => (int) $version->version_number,
                'effective_from' => $version->effective_from?->toDateString(),
                'effective_to' => $version->effective_to?->toDateString(),
                'status' => $this->enum($version->status),
                'rates' => $version->relationLoaded('lines') ? $version->lines->map(fn ($line) => [
                    'id' => (int) $line->getKey(),
                    'code' => $this->enum($line->rate_code),
                    'unit' => $this->enum($line->unit),
                    'rate' => (string) $line->rate,
                    'is_taxable' => (bool) $line->is_taxable,
                    'description' => $line->description,
                ])->values()->all() : [],
            ])->values()->all()),
            'assignments' => $this->whenLoaded('assignments', fn () => RentalAssignmentResource::collection($this->assignments)->resolve($request)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function enum(mixed $value): mixed { return $value instanceof \BackedEnum ? $value->value : $value; }
    private function party(mixed $party): ?array { return $party === null ? null : ['id' => (int) $party->getKey(), 'code' => $party->code ?? $party->customer_number ?? $party->supplier_number, 'name' => $party->display_name ?? $party->name]; }
    private function named(mixed $model): ?array { return $model === null ? null : ['id' => (int) $model->getKey(), 'code' => $model->code, 'name' => $model->name]; }
}
