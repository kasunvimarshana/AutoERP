<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class PurchaseDebitNoteResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'debit_note_number' => $this->debit_note_number,
            'debit_note_date' => $this->debit_note_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => str((string) $this->enumValue($this->status))->replace('_', ' ')->title()->toString(),
            'supplier_type' => $this->supplier_type,
            'supplier_id' => $this->supplier_id,
            'purchase_return_id' => $this->purchase_return_id,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'amount' => (string) $this->amount,
            'allocated_amount' => (string) $this->allocated_amount,
            'remaining_amount' => (string) $this->remaining_amount,
            'reason' => $this->reason,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
