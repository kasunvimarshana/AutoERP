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
            'row_version' => (int) $this->row_version,
            'invoice_number' => $this->invoice_number,
            'invoice_type' => $this->enumValue($this->invoice_type),
            'direction' => $this->enumValue($this->direction),
            'party_type' => $this->party_type,
            'party' => $this->partySnapshot(),
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'currency' => $this->currencySnapshot(),
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
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
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
                fn () => InvoiceSourceLineResource::collection($this->sourceLines)->resolve($request),
                [],
            ),
            'adjustments' => $this->whenLoaded(
                'adjustments',
                fn () => InvoiceAdjustmentResource::collection($this->adjustments)->resolve($request),
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
            'posting_plan' => $this->whenLoaded(
                'postingPlan',
                fn () => $this->postingPlan === null
                    ? null
                    : (new InvoicePostingPlanResource($this->postingPlan))->resolve($request),
            ),
            'credit_allocations' => $this->whenLoaded(
                'creditAllocations',
                fn () => InvoiceCreditAllocationResource::collection($this->creditAllocations)->resolve($request),
                [],
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function partySnapshot(): ?array
    {
        if ($this->party_id === null
            && $this->party_number_snapshot === null
            && $this->party_code_snapshot === null
            && $this->party_name_snapshot === null) {
            return null;
        }

        return [
            'id' => $this->party_id === null ? null : (int) $this->party_id,
            'number' => $this->party_number_snapshot,
            'code' => $this->party_code_snapshot,
            'name' => $this->party_name_snapshot,
            'legal_name' => $this->party_legal_name_snapshot,
            'tax_registration_number' => $this->party_tax_registration_snapshot,
            'email' => $this->party_email_snapshot,
            'phone' => $this->party_phone_snapshot,
        ];
    }

    private function currencySnapshot(): ?array
    {
        if ($this->currency_id === null
            && $this->currency_code_snapshot === null
            && $this->currency_name_snapshot === null
            && $this->currency_symbol_snapshot === null) {
            return null;
        }

        return [
            'id' => $this->currency_id === null ? null : (int) $this->currency_id,
            'code' => $this->currency_code_snapshot,
            'name' => $this->currency_name_snapshot,
            'symbol' => $this->currency_symbol_snapshot,
        ];
    }
}
