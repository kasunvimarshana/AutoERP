<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;

final class PurchaseReturnResource extends PurchaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'return_number' => $this->return_number,
            'return_date' => $this->return_date?->toDateString(),
            'return_type' => $this->enumValue($this->return_type),
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'source' => $this->sourceSummary(),
            'status' => $this->enumValue($this->status),
            'status_label' => $this->statusLabel($this->status),
            'supplier' => $this->whenLoaded('supplier', fn () => $this->summary($this->supplier, ['supplier_number', 'code', 'name', 'display_name'])),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'approval_required' => (bool) $this->approval_required,
            'capabilities' => $this->capabilities(),
            'affects_supplier_balance' => (bool) $this->affects_supplier_balance,
            'cost_basis' => $this->cost_basis === null ? null : (string) $this->cost_basis,
            'reason' => $this->reason,
            'subtotal' => (string) $this->subtotal,
            'adjustment_return_total' => (string) $this->adjustment_return_total,
            'grand_total' => (string) $this->grand_total,
            'debit_note_id' => $this->debit_note_id,
            'debit_note' => $this->whenLoaded('debitNote', fn () => $this->summary($this->debitNote, ['debit_note_number', 'status'])),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => (int) $line->getKey(),
                'source_line_type' => $line->source_line_type,
                'source_line_id' => (int) $line->source_line_id,
                'item' => $line->relationLoaded('item') ? $this->summary($line->item, ['code', 'name', 'sku']) : null,
                'item_variant' => $line->relationLoaded('variant') ? $this->summary($line->variant, ['code', 'name', 'sku']) : null,
                'uom' => $line->relationLoaded('uom') ? $this->summary($line->uom, ['code', 'name', 'symbol']) : null,
                'returned_quantity' => (string) $line->returned_quantity,
                'source_quantity' => (string) $line->source_quantity,
                'previously_returned_quantity' => (string) $line->previously_returned_quantity,
                'remaining_quantity' => (string) $line->remaining_quantity,
                'unit_price' => (string) $line->unit_price,
                'cost_basis' => $line->cost_basis === null ? null : (string) $line->cost_basis,
                'base_amount' => (string) $line->base_amount,
                'discount_amount' => (string) $line->discount_amount,
                'tax_amount' => (string) $line->tax_amount,
                'charge_amount' => (string) $line->charge_amount,
                'line_total' => (string) $line->line_total,
                'reason' => $line->reason,
            ])->all(), []),
            'adjustment_allocations' => $this->whenLoaded('adjustmentAllocations', fn () => $this->adjustmentAllocations->map(fn ($allocation): array => [
                'id' => (int) $allocation->getKey(),
                'adjustment_type' => $this->enumValue($allocation->adjustment_type),
                'effect' => $this->enumValue($allocation->effect),
                'source_amount' => (string) $allocation->source_amount,
                'previously_returned_amount' => (string) $allocation->previously_returned_amount,
                'returned_amount' => (string) $allocation->returned_amount,
                'remaining_amount' => (string) $allocation->remaining_amount,
            ])->all(), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function sourceSummary(): ?array
    {
        if ($this->source_type === 'goods_receipt_note' && $this->relationLoaded('sourceGoodsReceipt') && $this->sourceGoodsReceipt !== null) {
            return [
                'type' => $this->source_type,
                'id' => (int) $this->sourceGoodsReceipt->getKey(),
                'number' => $this->sourceGoodsReceipt->grn_number,
                'date' => $this->sourceGoodsReceipt->received_date?->toDateString(),
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
        $approvalRequired = (bool) $this->approval_required;

        return [
            'can_edit' => $status === 'draft',
            'can_approve' => $approvalRequired && $status === 'draft',
            'can_post' => ($approvalRequired && $status === 'approved') || (! $approvalRequired && $status === 'draft'),
            'can_cancel' => in_array($status, ['draft', 'approved'], true),
            'read_only' => in_array($status, ['posted', 'cancelled', 'reversed'], true),
        ];
    }
}
