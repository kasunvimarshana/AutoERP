<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Invoice\Http\Resources\Concerns\FormatsInvoiceResources;

final class InvoiceResource extends JsonResource
{
    use FormatsInvoiceResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'invoice_number' => $this->invoice_number,
            'invoice_type' => $this->enumValue($this->invoice_type),
            'direction' => $this->enumValue($this->direction),
            'party_type' => $this->party_type,
            'party' => $this->partySummary(),
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'currency' => $this->whenLoaded(
                'currency',
                fn () => $this->summary($this->currency, ['code', 'name', 'symbol']),
            ),
            'exchange_rate' => (string) $this->exchange_rate,
            'status' => $this->enumValue($this->status),
            'subtotal' => (string) $this->subtotal,
            'discount_total' => (string) $this->discount_total,
            'tax_total' => (string) $this->tax_total,
            'charge_total' => (string) $this->charge_total,
            'adjustment_total' => (string) $this->adjustment_total,
            'grand_total' => (string) $this->grand_total,
            'paid_total' => (string) $this->paid_total,
            'credit_total' => (string) $this->credit_total,
            'balance_due' => (string) $this->balance_due,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toISOString(),
            'posted_at' => $this->posted_at?->toISOString(),
            'lines' => $this->whenLoaded(
                'lines',
                fn () => InvoiceLineResource::collection($this->lines)->resolve($request),
                [],
            ),
            'sources' => $this->whenLoaded(
                'sources',
                fn () => InvoiceSourceResource::collection($this->sources)->resolve($request),
                [],
            ),
            'source_lines' => $this->whenLoaded(
                'sourceLines',
                fn () => InvoiceSourceLineResource::collection($this->sourceLines)
                    ->resolve($request),
                [],
            ),
            'adjustments' => $this->whenLoaded(
                'adjustments',
                fn () => InvoiceAdjustmentResource::collection($this->adjustments)
                    ->resolve($request),
                [],
            ),
            'adjustment_allocations' => $this->whenLoaded(
                'adjustmentAllocations',
                fn () => InvoiceAdjustmentAllocationResource::collection(
                    $this->adjustmentAllocations,
                )->resolve($request),
                [],
            ),
            'balance' => $this->whenLoaded(
                'balance',
                fn () => $this->balance === null
                    ? null
                    : (new InvoiceBalanceResource($this->balance))->resolve($request),
            ),
            'credit_allocations' => $this->whenLoaded(
                'creditAllocations',
                fn () => InvoiceCreditAllocationResource::collection($this->creditAllocations)
                    ->resolve($request),
                [],
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function partySummary(): mixed
    {
        return match ($this->party_type) {
            'customer' => $this->whenLoaded(
                'customer',
                fn () => $this->summary(
                    $this->customer,
                    ['customer_number', 'code', 'name', 'display_name'],
                ),
            ),
            'supplier' => $this->whenLoaded(
                'supplier',
                fn () => $this->summary(
                    $this->supplier,
                    ['supplier_number', 'code', 'name', 'display_name'],
                ),
            ),
            default => null,
        };
    }
}
