<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;
use Modules\Invoice\Http\Resources\Concerns\FormatsInvoiceResources;

final class InvoiceLineResource extends ModuleResource
{
    use FormatsInvoiceResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'line_number' => (int) $this->line_number,
            'item_id' => $this->item_id,
            'item' => $this->whenLoaded(
                'item',
                fn () => $this->summary($this->item, ['code', 'name', 'description']),
            ),
            'description' => $this->description,
            'line_type' => $this->enumValue($this->line_type),
            'quantity' => (string) $this->quantity,
            'uom_id' => $this->uom_id,
            'uom' => $this->whenLoaded(
                'uom',
                fn () => $this->summary($this->uom, ['code', 'name', 'symbol']),
            ),
            'unit_price' => (string) $this->unit_price,
            'discount_amount' => (string) $this->discount_amount,
            'tax_amount' => (string) $this->tax_amount,
            'charge_amount' => (string) $this->charge_amount,
            'line_total' => (string) $this->line_total,
            'source_line_type' => $this->source_line_type,
            'source_line_id' => $this->source_line_id,
            'metadata' => $this->metadata,
        ];
    }
}
