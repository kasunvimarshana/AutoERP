<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;

final class PurchaseDebitNoteResource extends PurchaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'debit_note_number' => $this->debit_note_number,
            'debit_note_date' => $this->debit_note_date?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => $this->statusLabel($this->status),
            'capabilities' => $this->capabilities(),
            'supplier_type' => $this->supplier_type,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier', fn () => $this->summary($this->supplier, ['supplier_number', 'code', 'name', 'display_name'])),
            'purchase_return_id' => $this->purchase_return_id,
            'purchase_return' => $this->whenLoaded('purchaseReturn', fn () => $this->summary($this->purchaseReturn, ['return_number', 'return_date', 'status'])),
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'source' => $this->sourceSummary(),
            'amount' => (string) $this->amount,
            'allocated_amount' => (string) $this->allocated_amount,
            'remaining_amount' => (string) $this->remaining_amount,
            'reason' => $this->reason,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function sourceSummary(): ?array
    {
        if ($this->relationLoaded('purchaseReturn') && $this->purchaseReturn !== null) {
            return [
                'type' => 'purchase_return',
                'id' => (int) $this->purchaseReturn->getKey(),
                'number' => $this->purchaseReturn->return_number,
                'date' => $this->purchaseReturn->return_date?->toDateString(),
            ];
        }

        if ($this->source_type === null && $this->source_id === null) {
            return null;
        }

        return [
            'type' => $this->source_type,
            'id' => $this->source_id === null ? null : (int) $this->source_id,
            'number' => null,
            'date' => null,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function capabilities(): array
    {
        $status = $this->enumValue($this->status);
        $remaining = (string) $this->remaining_amount;

        return [
            'can_approve' => $status === 'draft',
            'can_post' => $status === 'approved',
            'can_allocate' => $status === 'posted' && $this->hasPositiveAmount($remaining),
            'read_only' => in_array($status, ['posted', 'allocated', 'cancelled', 'reversed'], true),
        ];
    }

    private function hasPositiveAmount(string $amount): bool
    {
        return ! str_starts_with($amount, '-')
            && preg_replace('/[.0]/', '', $amount) !== '';
    }
}
