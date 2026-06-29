<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Invoice\Http\Resources\Concerns\FormatsInvoiceResources;

final class InvoiceLineResource extends JsonResource
{
    use FormatsInvoiceResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'line_number' => (int) $this->line_number,
            'item' => $this->itemSnapshot(),
            'description' => $this->description,
            'line_type' => $this->enumValue($this->line_type),
            'quantity' => (string) $this->quantity,
            'uom' => $this->uomSnapshot(),
            'unit_price' => (string) $this->unit_price,
            'discount_amount' => (string) $this->discount_amount,
            'tax_amount' => (string) $this->tax_amount,
            'taxes' => $this->tax_snapshot ?? [],
            'charge_amount' => (string) $this->charge_amount,
            'line_total' => (string) $this->line_total,
            'source_line_type' => $this->source_line_type,
            'source_line_id' => $this->source_line_id,
            'metadata' => $this->metadata,
        ];
    }

    private function itemSnapshot(): ?array
    {
        if ($this->item_code_snapshot === null && $this->item_name_snapshot === null) {
            return null;
        }

        return [
            'code' => $this->item_code_snapshot,
            'name' => $this->item_name_snapshot,
        ];
    }

    private function uomSnapshot(): ?array
    {
        if ($this->uom_code_snapshot === null && $this->uom_name_snapshot === null) {
            return null;
        }

        return [
            'code' => $this->uom_code_snapshot,
            'name' => $this->uom_name_snapshot,
        ];
    }
}
