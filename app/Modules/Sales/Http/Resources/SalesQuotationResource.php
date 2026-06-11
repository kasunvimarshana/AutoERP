<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesQuotationResource extends ModuleResource
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
            'status_label' => str((string) $this->enumValue($this->status))->replace('_', ' ')->title()->toString(),
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
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => (int) $line->getKey(),
                'line_number' => (int) $line->line_number,
                'item_id' => $line->item_id,
                'item' => $line->relationLoaded('item') ? $this->summary($line->item, ['code', 'name', 'sku']) : null,
                'item_variant' => $line->relationLoaded('variant') ? $this->summary($line->variant, ['code', 'name', 'sku']) : null,
                'description' => $line->description,
                'uom_id' => $line->uom_id,
                'uom' => $line->relationLoaded('uom') ? $this->summary($line->uom, ['code', 'name', 'symbol']) : null,
                'quantity' => (string) $line->quantity,
                'unit_price' => (string) $line->unit_price,
                'line_subtotal' => (string) $line->line_subtotal,
                'discount_calculation_type' => $line->discount_calculation_type,
                'discount_rate' => (string) $line->discount_rate,
                'discount_amount' => (string) $line->discount_amount,
                'tax_calculation_type' => $line->tax_calculation_type,
                'tax_rate' => (string) $line->tax_rate,
                'tax_amount' => (string) $line->tax_amount,
                'charge_calculation_type' => $line->charge_calculation_type,
                'charge_rate' => (string) $line->charge_rate,
                'charge_amount' => (string) $line->charge_amount,
                'line_total' => (string) $line->line_total,
                'status' => $line->status,
            ])->all(), []),
            'adjustments' => $this->whenLoaded('adjustments', fn () => SalesHeaderAdjustmentResource::collection($this->adjustments)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
