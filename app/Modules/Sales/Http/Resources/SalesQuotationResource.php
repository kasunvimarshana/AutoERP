<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesQuotationResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'quotation_number' => $this->quotation_number,
            'quotation_date' => $this->quotation_date?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'status' => $this->enumValue($this->status),
            'status_label' => $this->statusLabel($this->status),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name', 'payment_term_id', 'default_currency_id'])),
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'name', 'symbol'])),
            'exchange_rate' => (string) $this->exchange_rate,
            'subtotal' => (string) $this->subtotal,
            'line_discount_total' => (string) $this->line_discount_total,
            'line_tax_total' => (string) $this->line_tax_total,
            'line_charge_total' => (string) $this->line_charge_total,
            'header_increase_total' => (string) $this->header_increase_total,
            'header_decrease_total' => (string) $this->header_decrease_total,
            'grand_total' => (string) $this->grand_total,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toISOString(),
            'lines' => $this->whenLoaded(
                'lines',
                fn () => SalesQuotationLineResource::collection($this->lines)->resolve($request),
                [],
            ),
            'adjustments' => $this->whenLoaded('adjustments', fn () => SalesHeaderAdjustmentResource::collection($this->adjustments)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
