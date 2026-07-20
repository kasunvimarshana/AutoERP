<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RentalFinancialDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $party = $this->party_type === 'customer' ? $this->customer : $this->supplier;

        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'invoice_number' => $this->invoice_number,
            'invoice_type' => $this->enum($this->invoice_type),
            'direction' => $this->enum($this->direction),
            'status' => $this->enum($this->status),
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'party_type' => $this->party_type,
            'party' => $party === null ? null : [
                'id' => (int) $party->getKey(),
                'code' => $party->code ?? null,
                'name' => $party->name ?? $party->display_name ?? $party->legal_name ?? null,
            ],
            'currency' => $this->currency === null ? null : [
                'id' => (int) $this->currency->getKey(),
                'code' => $this->currency->code,
                'name' => $this->currency->name,
            ],
            'subtotal' => (string) $this->subtotal,
            'tax_total' => (string) $this->tax_total,
            'grand_total' => (string) $this->grand_total,
            'balance_due' => (string) $this->balance_due,
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
