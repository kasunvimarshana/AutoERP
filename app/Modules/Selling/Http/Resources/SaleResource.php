<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Selling\Models\Sale;

/** @mixin Sale */
final class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'row_version' => (int) $this->row_version,
            'sale_number' => $this->sale_number,
            'sale_date' => $this->sale_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'customer' => $this->whenLoaded('customer', fn (): array => [
                'id' => (int) $this->customer->getKey(),
                'name' => $this->customer->display_name ?: $this->customer->name,
                'customer_number' => $this->customer->customer_number,
            ]),
            'warehouse' => $this->whenLoaded('warehouse', fn (): array => [
                'id' => (int) $this->warehouse->getKey(),
                'name' => $this->warehouse->name,
            ]),
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn (): ?array => $this->warehouseLocation === null ? null : [
                'id' => (int) $this->warehouseLocation->getKey(),
                'code' => $this->warehouseLocation->code,
                'name' => $this->warehouseLocation->name,
            ]),
            'lines' => $this->whenLoaded('lines', fn (): array => $this->lines->map(static fn ($line): array => [
                'id' => (int) $line->getKey(),
                'line_number' => (int) $line->line_number,
                'description' => $line->description,
                'quantity' => (string) $line->quantity,
                'base_quantity' => (string) $line->base_quantity,
                'unit_price' => (string) $line->unit_price,
                'line_total' => (string) $line->line_total,
                'variant' => $line->variant === null ? null : ['id' => (int) $line->variant->getKey(), 'name' => $line->variant->name, 'code' => $line->variant->code],
                'batch' => $line->batch === null ? null : ['id' => (int) $line->batch->getKey(), 'name' => $line->batch->batch_number],
                'serial_number' => $line->serialNumber?->serial_number,
                'item' => [
                    'id' => (int) $line->item->getKey(),
                    'name' => $line->item->name,
                    'code' => $line->item->code,
                ],
                'uom' => [
                    'id' => (int) $line->uom->getKey(),
                    'name' => $line->uom->name,
                    'code' => $line->uom->code,
                ],
            ])->all()),
            'invoice' => $this->whenLoaded('invoice', fn (): ?array => $this->invoice === null ? null : [
                'id' => (int) $this->invoice->getKey(),
                'number' => $this->invoice->invoice_number,
                'status' => $this->invoice->status?->value ?? $this->invoice->status,
                'grand_total' => (string) $this->invoice->grand_total,
                'balance_due' => (string) $this->invoice->balance_due,
            ]),
            'returns' => $this->whenLoaded('returns', fn (): array => $this->returns->map(static fn ($return): array => [
                'id' => (int) $return->getKey(),
                'number' => $return->return_number,
                'date' => $return->return_date?->toDateString(),
                'reason' => $return->reason,
                'credit_amount' => (string) $return->credit_amount,
                'credit_allocated_amount' => (string) $return->credit_allocated_amount,
                'credit_available_amount' => (string) $return->credit_available_amount,
                'lines' => $return->lines->map(static fn ($line): array => [
                    'sale_line_id' => (int) $line->sale_line_id,
                    'quantity' => (string) $line->quantity,
                    'credit_amount' => (string) $line->credit_amount,
                ])->all(),
            ])->all()),
        ];
    }
}
